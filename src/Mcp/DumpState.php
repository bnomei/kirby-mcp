<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Mcp\Server\Session\SessionInterface;

final class DumpState
{
    private const TRACE_ID_KEY = 'kirby_mcp.session.last_trace_id';

    public static function reset(?SessionInterface $session = null): void
    {
        if ($session === null) {
            return;
        }

        $session->forget(self::TRACE_ID_KEY);
    }

    public static function setLastTraceId(?string $traceId, ?SessionInterface $session = null): void
    {
        if ($session === null) {
            return;
        }

        $normalized = is_string($traceId) && trim($traceId) !== '' ? trim($traceId) : null;

        if ($normalized === null) {
            $session->forget(self::TRACE_ID_KEY);
            return;
        }

        $session->set(self::TRACE_ID_KEY, $normalized);
    }

    public static function lastTraceId(?SessionInterface $session = null): ?string
    {
        if ($session === null) {
            return null;
        }

        $traceId = $session->get(self::TRACE_ID_KEY);

        return is_string($traceId) && trim($traceId) !== '' ? trim($traceId) : null;
    }
}
