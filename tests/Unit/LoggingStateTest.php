<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\LoggingState;
use Mcp\Schema\Enum\LoggingLevel;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;

it('stores logging level per session', function (): void {
    $sessionA = new Session(new InMemorySessionStore(60));
    $sessionB = new Session(new InMemorySessionStore(60));

    LoggingState::setLevel(LoggingLevel::Debug, $sessionA);

    expect(LoggingState::level($sessionA))->toBe(LoggingLevel::Debug);
    expect(LoggingState::level($sessionB))->toBe(LoggingLevel::Error);
});

it('evaluates log allowance against the session minimum', function (): void {
    $session = new Session(new InMemorySessionStore(60));

    LoggingState::setLevel(LoggingLevel::Warning, $session);

    expect(LoggingState::allows(LoggingLevel::Error, $session))->toBeTrue();
    expect(LoggingState::allows(LoggingLevel::Info, $session))->toBeFalse();
});
