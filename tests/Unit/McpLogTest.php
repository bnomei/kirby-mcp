<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\LoggingState;
use Bnomei\KirbyMcp\Mcp\McpLog;
use Mcp\Schema\Enum\LoggingLevel;
use Mcp\Schema\Notification\LoggingMessageNotification;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Server\RequestContext;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;

it('sends logging notifications when allowed', function (): void {
    $session = new Session(new InMemorySessionStore(60));
    LoggingState::setLevel(LoggingLevel::Debug, $session);

    $context = new RequestContext($session, new CallToolRequest('kirby_init', []));

    $fiber = new Fiber(function () use ($context): string {
        McpLog::log($context, LoggingLevel::Info, 'hello');

        return 'done';
    });

    $payload = $fiber->start();

    expect($payload)->toBeArray();
    expect($payload['type'] ?? null)->toBe('notification');
    expect($payload['notification'] ?? null)->toBeInstanceOf(LoggingMessageNotification::class);

    /** @var LoggingMessageNotification $notification */
    $notification = $payload['notification'];
    expect($notification->level)->toBe(LoggingLevel::Info);
    expect($notification->data)->toBe('hello');

    $fiber->resume();
});

it('skips logging notifications below the session minimum', function (): void {
    $session = new Session(new InMemorySessionStore(60));
    LoggingState::setLevel(LoggingLevel::Error, $session);

    $context = new RequestContext($session, new CallToolRequest('kirby_init', []));

    $fiber = new Fiber(function () use ($context): string {
        McpLog::log($context, LoggingLevel::Info, 'skip');

        return 'done';
    });

    $result = $fiber->start();

    expect($result)->toBeNull();
    expect($fiber->isTerminated())->toBeTrue();
    expect($fiber->getReturn())->toBe('done');
});

it('ignores null contexts', function (): void {
    McpLog::log(null, LoggingLevel::Info, 'noop');

    expect(true)->toBeTrue();
});
