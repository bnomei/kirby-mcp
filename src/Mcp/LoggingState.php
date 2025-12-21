<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Mcp\Schema\Enum\LoggingLevel;
use Mcp\Server\Protocol;
use Mcp\Server\Session\SessionInterface;

final class LoggingState
{
    private const LEGACY_SESSION_KEY = 'kirby_mcp.logging.level';
    private const DEFAULT_LEVEL = LoggingLevel::Error;

    public static function reset(?SessionInterface $session = null): void
    {
        if ($session === null) {
            return;
        }

        $session->forget(Protocol::SESSION_LOGGING_LEVEL);
        $session->forget(self::LEGACY_SESSION_KEY);
    }

    public static function setLevel(LoggingLevel $level, SessionInterface $session): void
    {
        $session->set(Protocol::SESSION_LOGGING_LEVEL, $level->value);
        $session->set(self::LEGACY_SESSION_KEY, $level->value);
    }

    public static function level(?SessionInterface $session = null): LoggingLevel
    {
        if ($session === null) {
            return self::DEFAULT_LEVEL;
        }

        $value = $session->get(Protocol::SESSION_LOGGING_LEVEL)
            ?? $session->get(self::LEGACY_SESSION_KEY);

        return LoggingLevel::tryFrom((string) $value) ?? self::DEFAULT_LEVEL;
    }

    public static function allows(LoggingLevel $messageLevel, ?SessionInterface $session = null): bool
    {
        return self::severity($messageLevel) >= self::severity(self::level($session));
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
