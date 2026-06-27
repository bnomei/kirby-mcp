<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Support;

use Kirby\CLI\CLI;
use Kirby\Cms\App;
use Throwable;

abstract class RuntimeCommand
{
    /**
     * @return array<string, mixed>
     */
    abstract public static function definition(): array;

    abstract public static function run(CLI $cli): void;

    protected static function kirbyOrEmitError(CLI $cli): ?App
    {
        $kirby = $cli->kirby(false);
        if ($kirby === null) {
            static::emit($cli, [
                'ok' => false,
                'error' => [
                    'class' => 'RuntimeException',
                    'message' => 'The Kirby installation could not be found.',
                    'code' => 0,
                ],
            ]);
            return null;
        }

        return $kirby;
    }

    /**
     * Resolves the commands root that runtime command templates must be
     * installed into. This MUST match the root the rest of the MCP stack
     * probes (`KirbyRoots::commandsRoot()` → `commands.local` first, then
     * `commands`), otherwise `kirby mcp:install`/`mcp:update` would write to a
     * different tree than `kirby_runtime_status`/`kirby_runtime_install`
     * inspect, leaving runtime-backed tools perpetually `needsRuntimeInstall`.
     *
     * `commands.local` is a getkirby/cli root that defaults to the Kirby
     * `commands` root but may be remapped via CLI roots config; preferring it
     * keeps the CLI entry points aligned with custom layouts.
     */
    protected static function resolveCommandsRoot(CLI $cli): string
    {
        $commandsRoot = $cli->root('commands.local');
        if (!is_string($commandsRoot) || $commandsRoot === '') {
            $commandsRoot = $cli->root('commands');
        }

        if (!is_string($commandsRoot) || $commandsRoot === '') {
            $commandsRoot = rtrim($cli->dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'commands';
        }

        return $commandsRoot;
    }

    /**
     * Strictly parses a pagination argument (`--cursor`/`--limit`) as a
     * base-10 integer.
     *
     * The previous `is_numeric($raw) ? (int) $raw : 0` pattern was unsafe: a
     * present-but-non-decimal value such as `0x10` or `1e2` is either rejected
     * by `is_numeric()` or mis-cast by `(int)`, collapsing to `0` — which these
     * commands interpret as "no pagination limit". A caller intending a bounded
     * page silently receives the entire index. This accepts only `^[+-]?\d+$`,
     * returns the integer for valid input, `$default` for an absent/empty arg,
     * and `false` for a present-but-invalid value (the caller emits an error).
     */
    protected static function parsePaginationArg(mixed $raw, int $default = 0): int|false
    {
        if ($raw === null) {
            return $default;
        }

        if (is_int($raw)) {
            return $raw;
        }

        if (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed === '') {
                return $default;
            }

            if (preg_match('/^[+-]?\d+$/', $trimmed) === 1) {
                return (int) $trimmed;
            }
        }

        return false;
    }

    /**
     * Resolves a non-negative pagination argument or emits an error payload and
     * returns null. Negative values clamp to 0 (consistent with the documented
     * "0 means no limit" / "0-based offset" semantics).
     */
    protected static function paginationArgOrEmitError(CLI $cli, string $arg): ?int
    {
        $value = self::parsePaginationArg($cli->arg($arg));
        if ($value === false) {
            static::emit($cli, [
                'ok' => false,
                'error' => static::errorArray(new \InvalidArgumentException(
                    sprintf('--%s must be a base-10 integer.', $arg),
                )),
            ]);

            return null;
        }

        return $value < 0 ? 0 : $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected static function emit(CLI $cli, array $payload): void
    {
        echo JsonMarkers::START . "\n";
        echo $cli->json($payload) . "\n";
        echo JsonMarkers::END . "\n";
    }

    protected static function traceForCli(CLI $cli, Throwable $exception, int $maxChars = 20000): ?string
    {
        if ($cli->arg('debug') !== true) {
            return null;
        }

        $trace = $exception->getTraceAsString();
        if ($maxChars > 0 && strlen($trace) > $maxChars) {
            return substr($trace, 0, $maxChars);
        }

        return $trace;
    }

    /**
     * @return array{class: string, message: string, code: int, trace?: string}
     */
    protected static function errorArray(Throwable $exception, ?string $trace = null): array
    {
        $payload = [
            'class' => $exception::class,
            'message' => $exception->getMessage(),
            'code' => (int) $exception->getCode(),
        ];

        if (is_string($trace) && $trace !== '') {
            $payload['trace'] = $trace;
        }

        return $payload;
    }
}
