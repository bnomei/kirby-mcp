<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

final class SessionState
{
    private static bool $initCalled = false;

    public static function reset(): void
    {
        self::$initCalled = false;
    }

    public static function markInitCalled(): void
    {
        self::$initCalled = true;
    }

    public static function initCalled(): bool
    {
        return self::$initCalled;
    }
}
