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
