<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Dumps;

use Kirby\Cms\App;

final class McpDumpContext
{
    public const ENV_TRACE_ID = 'KIRBY_MCP_TRACE_ID';

    private static ?string $traceId = null;

    public static function reset(): void
    {
        self::$traceId = null;
    }

    public static function setTraceId(?string $traceId): void
    {
        self::$traceId = is_string($traceId) && trim($traceId) !== '' ? trim($traceId) : null;
    }

    public static function traceId(): string
    {
        $env = getenv(self::ENV_TRACE_ID);
        if (is_string($env) && trim($env) !== '') {
            return trim($env);
        }

        if (self::$traceId === null) {
            self::$traceId = self::generateTraceId();
        }

        return self::$traceId;
    }

    public static function path(): ?string
    {
        $app = self::kirbyApp();
        if ($app === null) {
            return null;
        }

        $path = $app->request()->path()->toString(leadingSlash: true);
        return $path !== '' ? $path : '/';
    }

    public static function method(): ?string
    {
        $app = self::kirbyApp();
        if ($app === null) {
            return null;
        }

        return $app->request()->method();
    }

    public static function url(): ?string
    {
        $app = self::kirbyApp();
        if ($app === null) {
            return null;
        }

        return (string) $app->request()->url();
    }

    private static function kirbyApp(): ?App
    {
        if (!class_exists(App::class)) {
            return null;
        }

        if (!method_exists(App::class, 'instance')) {
            return null;
        }

        /** @var App|null $app */
        $app = App::instance(lazy: true);
        return $app;
    }

    public static function generateTraceId(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (\Throwable) {
            return 'trace_' . substr(sha1((string) microtime(true)), 0, 20);
        }
    }
}
