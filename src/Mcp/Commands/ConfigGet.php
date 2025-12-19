<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Commands;

use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommand;
use Kirby\CLI\CLI;
use Throwable;

final class ConfigGet extends RuntimeCommand
{
    /**
     * @return array{
     *   description: string,
     *   args: array<string, mixed>,
     *   command: callable(CLI): void
     * }
     */
    public static function definition(): array
    {
        return [
            'description' => 'Reads a Kirby config option by path and returns structured JSON for MCP (prefer the MCP resource `kirby://config/{option}` instead of calling this command directly).',
            'args' => [
                'path' => [
                    'description' => 'Option path in dot notation (e.g. vendor.plugin.someoption) or JSON array of segments (e.g. ["vendor","plugin","someoption"]).',
                ],
            ],
            'command' => [self::class, 'run'],
        ];
    }

    public static function run(CLI $cli): void
    {
        $kirby = self::kirbyOrEmitError($cli);
        if ($kirby === null) {
            return;
        }

        $raw = $cli->arg('path');
        if (!is_string($raw) || trim($raw) === '') {
            self::emit($cli, [
                'ok' => false,
                'error' => [
                    'class' => 'InvalidArgumentException',
                    'message' => 'Option path is required.',
                    'code' => 0,
                ],
            ]);
            return;
        }

        $raw = trim($raw);

        try {
            $path = self::normalizeOptionPath($raw);
            if ($path === '') {
                throw new \InvalidArgumentException('Option path must not be empty.');
            }

            $value = $kirby->option($path);
            $string = self::stringifyValue($value);

            $host = self::hostFromEnv();
            $prefix = is_string($host) && $host !== '' ? '[' . $host . '] ' : '';

            $line = $prefix . $path . ' = ' . $string;

            self::emit($cli, [
                'ok' => true,
                'host' => $host !== '' ? $host : null,
                'path' => $path,
                'value' => $string,
                'line' => $line,
            ]);
        } catch (Throwable $exception) {
            self::emit($cli, [
                'ok' => false,
                'input' => $raw,
                'error' => self::errorArray($exception, self::traceForCli($cli, $exception)),
            ]);
        }
    }

    private static function hostFromEnv(): string
    {
        foreach (['KIRBY_HOST', 'KIRBY_MCP_HOST'] as $name) {
            $value = getenv($name);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }

    private static function normalizeOptionPath(string $input): string
    {
        $input = trim($input);
        if ($input === '') {
            return '';
        }

        $decoded = null;
        $first = $input[0] ?? '';
        if ($first === '[' || $first === '{' || $first === '"') {
            try {
                $decoded = json_decode($input, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $decoded = null;
            }
        }

        if ($decoded === null) {
            return $input;
        }

        if (is_string($decoded)) {
            return trim($decoded);
        }

        if (!is_array($decoded)) {
            return $input;
        }

        // JSON list: treat as path segments and join with dots.
        if (array_is_list($decoded)) {
            $segments = [];
            self::flattenSegments($decoded, $segments);

            $segments = array_values(array_filter(
                array_map(static fn (string $s): string => trim($s), $segments),
                static fn (string $s): bool => $s !== '',
            ));

            return implode('.', $segments);
        }

        // JSON object: flatten keys to dot notation (must resolve to a single leaf path).
        $paths = [];
        self::collectLeafPaths($decoded, '', $paths);
        $paths = array_values(array_unique(array_filter($paths, static fn (string $p): bool => trim($p) !== '')));

        if (count($paths) === 1) {
            return $paths[0] ?? '';
        }

        if ($paths === []) {
            throw new \InvalidArgumentException('JSON input did not contain an option path.');
        }

        throw new \InvalidArgumentException('JSON input must resolve to exactly one option path.');
    }

    /**
     * @param array<mixed> $value
     * @param array<int, string> $segments
     */
    private static function flattenSegments(array $value, array &$segments): void
    {
        foreach ($value as $item) {
            if (is_array($item)) {
                self::flattenSegments($item, $segments);
                continue;
            }

            if (is_string($item)) {
                $segments[] = $item;
                continue;
            }

            if (is_int($item) || is_float($item) || is_bool($item)) {
                $segments[] = (string) $item;
            }
        }
    }

    /**
     * @param array<mixed> $value
     * @param array<int, string> $paths
     */
    private static function collectLeafPaths(array $value, string $prefix, array &$paths): void
    {
        foreach ($value as $key => $item) {
            if (!is_string($key) && !is_int($key)) {
                continue;
            }

            $key = trim((string) $key);
            if ($key === '') {
                continue;
            }

            $path = $prefix !== '' ? $prefix . '.' . $key : $key;

            if (is_array($item)) {
                self::collectLeafPaths($item, $path, $paths);
                continue;
            }

            $paths[] = $path;
        }
    }

    private static function stringifyValue(mixed $value): string
    {
        if ($value instanceof \Closure) {
            return 'Closure type';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_object($value)) {
            return $value::class;
        }

        return gettype($value);
    }
}
