<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Mcp\Schema\Enum\LoggingLevel;

final class LoggingState
{
    private static LoggingLevel $level = LoggingLevel::Error;

    public static function setLevel(LoggingLevel $level): void
    {
        self::$level = $level;
    }

    public static function level(): LoggingLevel
    {
        return self::$level;
    }

    public static function allows(LoggingLevel $messageLevel): bool
    {
        return self::severity($messageLevel) >= self::severity(self::$level);
    }

    private static function severity(LoggingLevel $level): int
    {
        return match ($level) {
            LoggingLevel::Debug => 0,
            LoggingLevel::Info => 1,
            LoggingLevel::Notice => 2,
            LoggingLevel::Warning => 3,
            LoggingLevel::Error => 4,
            LoggingLevel::Critical => 5,
            LoggingLevel::Alert => 6,
            LoggingLevel::Emergency => 7,
        };
    }
}
