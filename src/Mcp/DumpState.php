<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

final class DumpState
{
    private static ?string $lastTraceId = null;

    public static function reset(): void
    {
        self::$lastTraceId = null;
    }

    public static function setLastTraceId(?string $traceId): void
    {
        self::$lastTraceId = is_string($traceId) && trim($traceId) !== '' ? trim($traceId) : null;
    }

    public static function lastTraceId(): ?string
    {
        return self::$lastTraceId;
    }
}
