<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Handlers\RequireInitForToolsHandler;
use Bnomei\KirbyMcp\Mcp\SessionState;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;

it('blocks tool calls until kirby_init is called', function (): void {
    SessionState::reset();

    $handler = new RequireInitForToolsHandler();
    $request = (new CallToolRequest('kirby_search', ['query' => 'config options']))->withId(1);
    $session = new Session(new InMemorySessionStore(60));

    expect($handler->supports($request))->toBeTrue();

    $response = $handler->handle($request, $session);

    expect($response->result)->toBeInstanceOf(CallToolResult::class);
    expect($response->result->isError)->toBeTrue();

    $content = $response->result->content;
    expect($content)->toHaveCount(1);
    expect($content[0])->toBeInstanceOf(TextContent::class);
    expect((string) $content[0]->text)->toContain('kirby_init');
    expect((string) $content[0]->text)->toContain('kirby_search');
});

it('does not block kirby_init tool calls', function (): void {
    SessionState::reset();

    $handler = new RequireInitForToolsHandler();
    $request = (new CallToolRequest('kirby_init', []))->withId(1);

    expect($handler->supports($request))->toBeFalse();
});

it('does not block tool calls after kirby_init', function (): void {
    SessionState::reset();
    SessionState::markInitCalled();

    $handler = new RequireInitForToolsHandler();
    $request = (new CallToolRequest('kirby_search', ['query' => 'config options']))->withId(1);

    expect($handler->supports($request))->toBeFalse();
});
