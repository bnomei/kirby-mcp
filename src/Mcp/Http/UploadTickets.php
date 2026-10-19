<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Http;

/** Private, user-bound, single-use capabilities. Claims consume even failed transfers. */
final class UploadTickets
{
    private readonly string $directory;

    public function __construct(string $projectRoot)
    {
        $this->directory = $projectRoot . '/.kirby-mcp/uploads';
        if (is_link($projectRoot . '/.kirby-mcp') || is_link($this->directory)) {
            throw new \RuntimeException('Refusing symlinked upload ticket storage.');
        }
    }

    public function issue(?string $userId, array $input, array $preview): array
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('Unable to create upload ticket store.');
        }
        if (!chmod($this->directory, 0700)) {
            throw new \RuntimeException('Upload ticket store must be private.');
        }
        foreach (glob($this->directory . '/*.json') ?: [] as $old) {
            if (filemtime($old) < time() - 600) {
                @unlink($old);
            }
        }
        $ticket = bin2hex(random_bytes(32));
        $expiresAt = time() + 600;
        $path = $this->path($ticket);
        $handle = fopen($path, 'x');
        if ($handle === false) {
            throw new \RuntimeException('Unable to create upload ticket.');
        }
        try {
            chmod($path, 0600);
            $json = json_encode(compact('userId', 'input', 'preview', 'expiresAt'), JSON_THROW_ON_ERROR);
            if (fwrite($handle, $json) !== strlen($json)) {
                throw new \RuntimeException('Unable to write upload ticket.');
            }
        } finally {
            fclose($handle);
        }
        return ['ticket' => $ticket, 'expiresAt' => $expiresAt, 'maxBytes' => $preview['maxBytes']];
    }

    public function claim(string $ticket, ?string $userId): array
    {
        $path = $this->path($ticket);
        if (!is_file($path) || is_link($path)) {
            throw new \RuntimeException('Invalid or consumed upload ticket.');
        }
        $handle = @fopen($path, 'r+');
        if ($handle === false) {
            throw new \RuntimeException('Invalid or consumed upload ticket.');
        }
        try {
            if (!flock($handle, LOCK_EX)) {
                throw new \RuntimeException('Unable to claim upload ticket.');
            }
            $data = json_decode(stream_get_contents($handle), true, 64, JSON_THROW_ON_ERROR);
            if (!is_array($data) || ($data['userId'] ?? null) !== $userId || ($data['expiresAt'] ?? 0) <= time()) {
                throw new \RuntimeException('Expired or foreign upload ticket.');
            }
            // Truncate under the lock so a concurrent opener cannot redeem an unlinked inode.
            if (!ftruncate($handle, 0) || !unlink($path)) {
                throw new \RuntimeException('Unable to consume upload ticket.');
            }
            return $data;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function path(string $ticket): string
    {
        if (preg_match('/^[a-f0-9]{64}$/D', $ticket) !== 1) {
            throw new \InvalidArgumentException('Invalid upload ticket.');
        }
        return $this->directory . '/' . hash('sha256', $ticket) . '.json';
    }
}
