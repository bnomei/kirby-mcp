<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Handlers\SetLogLevelHandler;
use Bnomei\KirbyMcp\Mcp\LoggingState;
use Mcp\Schema\Enum\LoggingLevel;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Request\SetLogLevelRequest;
use Mcp\Schema\Result\EmptyResult;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;

it('handles log level requests', function (): void {
    $handler = new SetLogLevelHandler();
    $session = new Session(new InMemorySessionStore(60));

    $request = (new SetLogLevelRequest(LoggingLevel::Debug))->withId('1');

    expect($handler->supports($request))->toBeTrue();

    $response = $handler->handle($request, $session);

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->result)->toBeInstanceOf(EmptyResult::class);
    expect(LoggingState::level($session))->toBe(LoggingLevel::Debug);
});

it('rejects non log level requests', function (): void {
    $handler = new SetLogLevelHandler();

    $request = new CallToolRequest('kirby_tool_suggest', []);

    expect($handler->supports($request))->toBeFalse();
});
