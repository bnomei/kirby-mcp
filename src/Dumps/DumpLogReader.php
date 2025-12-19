<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Dumps;

use Bnomei\KirbyMcp\Support\Json;

final class DumpLogReader
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function tail(
        ?string $projectRoot = null,
        ?string $traceId = null,
        ?string $path = null,
        int $limit = 50,
    ): array {
        $traceId = is_string($traceId) && trim($traceId) !== '' ? trim($traceId) : null;
        $path = self::normalizePath($path);
        $limit = max(0, $limit);

        $file = DumpLogWriter::filePath($projectRoot);
        if (!is_file($file)) {
            return [];
        }

        $contents = self::readLocked($file);
        if (!is_string($contents) || trim($contents) === '') {
            return [];
        }

        $lines = preg_split("/\\r\\n|\\n|\\r/", $contents);
        if (!is_array($lines)) {
            return [];
        }

        /** @var array<string, array<string, mixed>> $eventsById */
        $eventsById = [];

        /** @var array<string, array<int, array<string, mixed>>> $pendingUpdates */
        $pendingUpdates = [];

        foreach ($lines as $line) {
            if (!is_string($line)) {
                continue;
            }

            $line = trim($line);
            if ($line === '') {
                continue;
            }

            try {
                $row = Json::decodeString($line);
            } catch (\Throwable) {
                continue;
            }

            $type = $row['type'] ?? null;
            if (!is_string($type) || $type === '') {
                continue;
            }

            $rowTraceId = $row['traceId'] ?? null;
            if ($traceId !== null && (!is_string($rowTraceId) || $rowTraceId !== $traceId)) {
                continue;
            }

            $rowPath = self::normalizePath($row['path'] ?? null);
            if ($path !== null && $rowPath !== $path) {
                continue;
            }

            $id = $row['id'] ?? null;
            if (!is_string($id) || $id === '') {
                continue;
            }

            if ($type === 'dump') {
                $eventsById[$id] = $row;

                if (isset($pendingUpdates[$id])) {
                    foreach ($pendingUpdates[$id] as $update) {
                        self::applyUpdate($eventsById[$id], $update);
                    }
                    unset($pendingUpdates[$id]);
                }

                continue;
            }

            if ($type === 'update') {
                $set = $row['set'] ?? null;
                if (!is_array($set)) {
                    continue;
                }

                if (isset($eventsById[$id])) {
                    self::applyUpdate($eventsById[$id], $set);
                } else {
                    $pendingUpdates[$id] ??= [];
                    $pendingUpdates[$id][] = $set;
                }
            }
        }

        $events = array_values($eventsById);
        usort($events, static function (array $a, array $b): int {
            $ta = is_numeric($a['t'] ?? null) ? (float) $a['t'] : 0.0;
            $tb = is_numeric($b['t'] ?? null) ? (float) $b['t'] : 0.0;
            return $ta <=> $tb;
        });

        if ($limit > 0 && count($events) > $limit) {
            $events = array_slice($events, -$limit);
        }

        return $events;
    }

    private static function normalizePath(mixed $path): ?string
    {
        if (!is_string($path)) {
            return null;
        }

        $path = trim($path);
        if ($path === '') {
            return null;
        }

        if ($path === '/') {
            return '/';
        }

        return str_starts_with($path, '/') ? $path : '/' . $path;
    }

    private static function readLocked(string $path): ?string
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return null;
        }

        try {
            @flock($handle, LOCK_SH);
            $contents = stream_get_contents($handle);
            return is_string($contents) ? $contents : null;
        } finally {
            @flock($handle, LOCK_UN);
            @fclose($handle);
        }
    }

    /**
     * @param array<string, mixed> $event
     * @param array<string, mixed> $set
     */
    private static function applyUpdate(array &$event, array $set): void
    {
        foreach ($set as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            $event[$key] = $value;
        }
    }
}
