<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\DumpState;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;

it('stores last trace id per session', function (): void {
    $sessionA = new Session(new InMemorySessionStore(60));
    $sessionB = new Session(new InMemorySessionStore(60));

    DumpState::setLastTraceId('trace-123', $sessionA);

    expect(DumpState::lastTraceId($sessionA))->toBe('trace-123');
    expect(DumpState::lastTraceId($sessionB))->toBeNull();
});

it('clears trace id when reset is called', function (): void {
    $session = new Session(new InMemorySessionStore(60));
    DumpState::setLastTraceId('trace-456', $session);

    DumpState::reset($session);

    expect(DumpState::lastTraceId($session))->toBeNull();
});
