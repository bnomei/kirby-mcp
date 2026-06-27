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
