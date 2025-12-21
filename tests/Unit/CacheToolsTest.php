<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\ToolIndex;
use Bnomei\KirbyMcp\Mcp\Tools\CacheTools;
use Bnomei\KirbyMcp\Support\StaticCache;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\RequestContext;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;

it('rejects invalid cache scope', function (): void {
    $tools = new CacheTools();

    $result = $tools->clearCache('nope');

    expect($result)->toBeArray();
    expect($result['ok'])->toBeFalse();
    expect($result['message'])->toContain('Invalid scope');
});

it('requires prefix when scope=prefix', function (): void {
    $tools = new CacheTools();

    $result = $tools->clearCache('prefix');

    expect($result)->toBeArray();
    expect($result['ok'])->toBeFalse();
    expect($result['message'])->toBe('prefix is required when scope=prefix.');
});

it('clears prefixed static cache entries', function (): void {
    $tools = new CacheTools();

    StaticCache::set('cli:test', 'value', 60);
    expect(StaticCache::get('cli:test'))->toBe('value');

    $result = $tools->clearCache('prefix', 'cli:');

    expect($result['ok'])->toBeTrue();
    expect(StaticCache::get('cli:test'))->toBeNull();
    expect($result['staticCache']['prefix'])->toBe('cli:');
    expect($result['staticCache']['removed'])->toBeInt();
});

it('clears tool index cache and returns structured results with context', function (): void {
    ToolIndex::all();

    $tools = new CacheTools();
    $session = new Session(new InMemorySessionStore(60));
    $context = new RequestContext($session, new CallToolRequest('kirby_cache_clear', []));

    $result = $tools->clearCache('tools', context: $context);

    expect($result)->toBeInstanceOf(CallToolResult::class);

    $payload = $result->structuredContent ?? null;
    expect($payload)->toBeArray();
    expect($payload['ok'])->toBeTrue();
    expect($payload['toolIndex']['cleared'])->toBeTrue();
});
