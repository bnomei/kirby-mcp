<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Mcp\Server\Session\SessionInterface;

final class SessionState
{
    private const INIT_KEY = 'kirby_mcp.session.init';

    public static function reset(?SessionInterface $session = null): void
    {
        if ($session === null) {
            return;
        }

        $session->forget(self::INIT_KEY);
    }

    public static function markInitCalled(?SessionInterface $session = null): void
    {
        if ($session === null) {
            return;
        }

        $session->set(self::INIT_KEY, true);
    }

    public static function initCalled(?SessionInterface $session = null): bool
    {
        if ($session === null) {
            return false;
        }

        return (bool) $session->get(self::INIT_KEY, false);
    }
}
