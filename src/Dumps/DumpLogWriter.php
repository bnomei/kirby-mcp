<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Dumps;

use Bnomei\KirbyMcp\Project\KirbyMcpConfig;

final class DumpLogWriter
{
    public const DIR_NAME = '.kirby-mcp';
    public const FILE_NAME = 'dumps.jsonl';
    private const DEFAULT_KEEP_FRACTION = 0.5;
    private const ENV_DUMPS_ENABLED = 'KIRBY_MCP_DUMPS_ENABLED';

    public static function filePath(?string $projectRoot = null): string
    {
        $projectRoot = DumpProjectRootResolver::resolve($projectRoot);

        return rtrim($projectRoot, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . self::DIR_NAME
            . DIRECTORY_SEPARATOR
            . self::FILE_NAME;
    }

    public static function maxBytes(string $projectRoot): int
    {
        return KirbyMcpConfig::load($projectRoot)->dumpsMaxBytes();
    }

    public static function enabled(string $projectRoot): bool
    {
        $env = getenv(self::ENV_DUMPS_ENABLED);
        if (is_string($env) && trim($env) !== '') {
            $value = strtolower(trim($env));
            if ($value === '0' || $value === 'false' || $value === 'off' || $value === 'no') {
                return false;
            }

            if ($value === '1' || $value === 'true' || $value === 'on' || $value === 'yes') {
                return true;
            }
        }

        return KirbyMcpConfig::load($projectRoot)->dumpsEnabled();
    }

    /**
     * @param array<string, mixed> $entry
     */
    public static function append(array $entry, ?string $projectRoot = null): void
    {
        $projectRoot = DumpProjectRootResolver::resolve($projectRoot);
        if (self::enabled($projectRoot) !== true) {
            return;
        }

        $dir = rtrim($projectRoot, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . self::DIR_NAME;

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $path = $dir . DIRECTORY_SEPARATOR . self::FILE_NAME;

        $json = json_encode(
            $entry,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR,
        );

        if (!is_string($json) || $json === '') {
            return;
        }

        $line = $json . "\n";
        $lineBytes = strlen($line);

        $maxBytes = self::maxBytes($projectRoot);
        if ($maxBytes > 0 && $lineBytes > $maxBytes) {
            $fallback = json_encode([
                'type' => 'dump',
                't' => microtime(true),
                'traceId' => $entry['traceId'] ?? null,
                'id' => $entry['id'] ?? null,
                'path' => $entry['path'] ?? null,
                'error' => 'mcp_dump entry exceeds dumps.maxBytes and was not written',
                'entryBytes' => $lineBytes,
                'maxBytes' => $maxBytes,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

            if (!is_string($fallback) || $fallback === '') {
                return;
            }

            $line = $fallback . "\n";
            $lineBytes = strlen($line);
        }

        if ($maxBytes > 0 && $lineBytes > $maxBytes) {
            return;
        }

        $handle = @fopen($path, 'c+');
        if ($handle === false) {
            return;
        }

        try {
            if (@flock($handle, LOCK_EX) !== true) {
                return;
            }

            $size = 0;
            $stat = fstat($handle);
            if (is_array($stat) && isset($stat['size'])) {
                $size = (int) $stat['size'];
            }

            if ($maxBytes > 0 && ($size + $lineBytes) > $maxBytes) {
                self::compactInPlace($handle, $maxBytes, $lineBytes, self::DEFAULT_KEEP_FRACTION);
            }

            @fseek($handle, 0, SEEK_END);
            @fwrite($handle, $line);
            @fflush($handle);
        } finally {
            @flock($handle, LOCK_UN);
            @fclose($handle);
        }
    }

    /**
     * Shrinks the log file by keeping only the newest fraction of lines.
     *
     * @param resource $handle
     */
    private static function compactInPlace($handle, int $maxBytes, int $incomingBytes, float $keepFraction): void
    {
        $maxBytes = max(0, $maxBytes);
        $incomingBytes = max(0, $incomingBytes);
        $keepFraction = max(0.0, min(1.0, $keepFraction));

        if ($maxBytes === 0) {
            return;
        }

        @rewind($handle);
        $contents = stream_get_contents($handle);
        if (!is_string($contents) || trim($contents) === '') {
            @ftruncate($handle, 0);
            @rewind($handle);
            return;
        }

        $lines = preg_split("/\\r\\n|\\n|\\r/", rtrim($contents, "\r\n"));
        if (!is_array($lines) || $lines === []) {
            @ftruncate($handle, 0);
            @rewind($handle);
            return;
        }

        $total = count($lines);
        $keepCount = (int) ceil($total * $keepFraction);
        $keepCount = max(0, min($total, $keepCount));

        $kept = $keepCount > 0 ? array_slice($lines, -$keepCount) : [];

        while (true) {
            $newContents = $kept !== [] ? implode("\n", $kept) . "\n" : '';

            if ((strlen($newContents) + $incomingBytes) <= $maxBytes) {
                @ftruncate($handle, 0);
                @rewind($handle);

                if ($newContents !== '') {
                    @fwrite($handle, $newContents);
                    @fflush($handle);
                }

                return;
            }

            if (count($kept) <= 1) {
                $kept = [];
                continue;
            }

            array_shift($kept);
        }
    }
}
