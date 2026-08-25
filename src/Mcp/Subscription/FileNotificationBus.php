<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Subscription;

use Mcp\JsonRpc\MessageFactory;
use Mcp\Schema\JsonRpc\Notification;
use Mcp\Server\Subscription\NotificationBusInterface;
use RuntimeException;
use Throwable;

/** Local-filesystem notification bus for separate HTTP/FPM workers. */
final class FileNotificationBus implements NotificationBusInterface
{
    private const MAX_BYTES = 1048576;
    private const MAX_ENTRIES = 256;
    private const TTL_SECONDS = 120;

    private readonly string $directory;
    private readonly string $statePath;
    private readonly string $lockPath;
    private bool $muted = true;

    public function __construct(string $projectRoot)
    {
        $base = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.kirby-mcp';
        if (is_link($base)) {
            throw new RuntimeException('Refusing symlinked Kirby MCP storage.');
        }
        if (!is_dir($base) && (!@mkdir($base, 0700) && !is_dir($base))) {
            throw new RuntimeException('Unable to create the Kirby MCP storage directory.');
        }

        $this->directory = $base . DIRECTORY_SEPARATOR . 'http-notifications';
        $this->statePath = $this->directory . DIRECTORY_SEPARATOR . 'state.json';
        $this->lockPath = $this->directory . DIRECTORY_SEPARATOR . 'state.lock';
        if (is_link($this->directory)) {
            throw new RuntimeException('Refusing symlinked HTTP notification storage.');
        }
        if (!is_dir($this->directory) && (!@mkdir($this->directory, 0700) && !is_dir($this->directory))) {
            throw new RuntimeException('Unable to create HTTP notification storage.');
        }
        if (!@chmod($this->directory, 0700) || (fileperms($this->directory) & 0777) !== 0700) {
            throw new RuntimeException('HTTP notification storage must have 0700 permissions.');
        }

        foreach ([$this->statePath, $this->lockPath] as $path) {
            if (is_link($path)) {
                throw new RuntimeException('Refusing symlinked HTTP notification storage.');
            }
        }

        $lock = @fopen($this->lockPath, 'c+b');
        if ($lock === false) {
            throw new RuntimeException('Unable to open HTTP notification lock.');
        }
        if (!@chmod($this->lockPath, 0600) || (fileperms($this->lockPath) & 0777) !== 0600) {
            fclose($lock);

            throw new RuntimeException('HTTP notification lock must have 0600 permissions.');
        }
        fclose($lock);

        $this->withLock(LOCK_EX, function (): void {
            if (!is_file($this->statePath)) {
                $this->writeState($this->emptyState());
            } else {
                $this->readState();
            }
        });
    }

    public function activate(): void
    {
        $this->muted = false;
    }

    public function publish(Notification $notification): void
    {
        if ($this->muted) {
            return;
        }

        $this->withLock(LOCK_EX, function () use ($notification): void {
            $state = $this->readState();
            $now = microtime(true);
            $state['entries'] = $this->retained($state['entries'], $now);
            $entry = [
                'sequence' => $state['next'],
                'publishedAt' => $now,
                'notification' => $notification->jsonSerialize(),
            ];
            $singleEntryState = json_encode(
                ['version' => 1, 'next' => $state['next'] + 1, 'entries' => [$entry]],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
            );
            if (strlen($singleEntryState) > self::MAX_BYTES) {
                return;
            }

            $state['entries'][] = $entry;
            ++$state['next'];
            $state['entries'] = array_slice($state['entries'], -self::MAX_ENTRIES);
            $this->writeState($state);
        });
    }

    public function cursor(): int
    {
        return $this->withLock(LOCK_EX, function (): int {
            $state = $this->readState();
            $retained = $this->retained($state['entries'], microtime(true));
            if ($retained !== $state['entries']) {
                $state['entries'] = $retained;
                $this->writeState($state);
            }

            return $state['next'];
        });
    }

    public function since(int $cursor): array
    {
        return $this->withLock(LOCK_EX, function () use ($cursor): array {
            $state = $this->readState();
            $entries = $this->retained($state['entries'], microtime(true));
            $notifications = [];
            $factory = MessageFactory::make();
            foreach ($entries as $entry) {
                if (($entry['sequence'] ?? -1) < $cursor || !is_array($entry['notification'] ?? null)) {
                    continue;
                }
                try {
                    $messages = $factory->create(json_encode($entry['notification'], JSON_THROW_ON_ERROR));
                    if (($messages[0] ?? null) instanceof Notification) {
                        $notifications[] = $messages[0];
                    }
                } catch (Throwable) {
                    // Skip malformed entries while still advancing to the head.
                }
            }
            if ($entries !== $state['entries']) {
                $state['entries'] = $entries;
                $this->writeState($state);
            }

            return [$notifications, $state['next']];
        });
    }

    /** @return array{version:int,next:int,entries:array<int, array<string, mixed>>} */
    private function readState(): array
    {
        try {
            $size = @filesize($this->statePath);
            if (is_link($this->statePath) || !is_file($this->statePath) || !is_int($size) || $size > self::MAX_BYTES) {
                throw new RuntimeException('Unsafe or oversized notification state.');
            }
            $state = json_decode((string) file_get_contents($this->statePath), true, flags: JSON_THROW_ON_ERROR);
            if (!is_array($state) || ($state['version'] ?? null) !== 1 || !is_int($state['next'] ?? null) || $state['next'] < 0 || !is_array($state['entries'] ?? null)) {
                throw new RuntimeException('Invalid notification state.');
            }

            return $state;
        } catch (Throwable $exception) {
            error_log('Kirby MCP reset corrupt HTTP notification state: ' . $exception->getMessage());
            $state = $this->emptyState();
            $this->writeState($state);

            return $state;
        }
    }

    /** @return array{version:int,next:int,entries:array{}} */
    private function emptyState(): array
    {
        return ['version' => 1, 'next' => 0, 'entries' => []];
    }

    /** @param array<int, array<string, mixed>> $entries @return array<int, array<string, mixed>> */
    private function retained(array $entries, float $now): array
    {
        return array_values(array_filter($entries, static fn (mixed $entry): bool => is_array($entry)
            && is_int($entry['sequence'] ?? null)
            && is_numeric($entry['publishedAt'] ?? null)
            && (float) $entry['publishedAt'] >= $now - self::TTL_SECONDS));
    }

    /** @param array<string, mixed> $state */
    private function writeState(array $state): void
    {
        $json = $this->encodeBoundedState($state);
        $temp = tempnam($this->directory, '.state-');
        if ($temp === false || is_link($this->statePath)) {
            throw new RuntimeException('Unable to safely write HTTP notification state.');
        }
        $handle = fopen($temp, 'wb');
        if ($handle === false) {
            @unlink($temp);
            throw new RuntimeException('Unable to write HTTP notification state.');
        }
        try {
            $written = 0;
            $length = strlen($json);
            while ($written < $length) {
                $bytes = fwrite($handle, substr($json, $written));
                if (!is_int($bytes) || $bytes < 1) {
                    throw new RuntimeException('Unable to write HTTP notification state.');
                }
                $written += $bytes;
            }
            if (!fflush($handle)) {
                throw new RuntimeException('Unable to flush HTTP notification state.');
            }
            if (function_exists('fsync')) {
                @fsync($handle);
            }
            if (!@chmod($temp, 0600) || (fileperms($temp) & 0777) !== 0600) {
                throw new RuntimeException('HTTP notification state must have 0600 permissions.');
            }
            fclose($handle);
            $handle = null;
            if (!@rename($temp, $this->statePath)) {
                throw new RuntimeException('Unable to replace HTTP notification state.');
            }
            if (!@chmod($this->statePath, 0600) || (fileperms($this->statePath) & 0777) !== 0600) {
                throw new RuntimeException('HTTP notification state must have 0600 permissions.');
            }
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            if (is_file($temp)) {
                @unlink($temp);
            }
        }
    }

    /** @param array<string, mixed> $state */
    private function encodeBoundedState(array $state): string
    {
        do {
            $json = json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            if (strlen($json) <= self::MAX_BYTES) {
                return $json;
            }

            if (!is_array($state['entries'] ?? null) || $state['entries'] === []) {
                throw new RuntimeException('HTTP notification state exceeds its size limit.');
            }

            array_shift($state['entries']);
        } while (true);
    }

    /** @param LOCK_EX|LOCK_SH $operation */
    private function withLock(int $operation, callable $callback): mixed
    {
        if (is_link($this->lockPath)) {
            throw new RuntimeException('Refusing symlinked HTTP notification lock.');
        }
        $handle = fopen($this->lockPath, 'c+b');
        if ($handle === false || !flock($handle, $operation)) {
            throw new RuntimeException('Unable to lock HTTP notification storage.');
        }
        try {
            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
