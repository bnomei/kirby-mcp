<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Tools\MetaTools;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Server\RequestContext;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;

it('suggests the blueprint index tool for blueprint queries', function (): void {
    $session = new Session(new InMemorySessionStore(60));
    $context = new RequestContext($session, new CallToolRequest('kirby_tool_suggest', []));

    $data = (new MetaTools())->suggestTools(query: 'blueprint yaml', context: $context);

    expect($data)->toBeInstanceOf(CallToolResult::class);
    $payload = $data->structuredContent ?? null;

    expect($payload)->toBeArray();
    expect($payload)->toHaveKey('suggestions');
    expect($payload['suggestions'][0]['tool'])->toBe('kirby_blueprints_index');
    expect($payload['initRecommended'])->toBeTrue();
});
