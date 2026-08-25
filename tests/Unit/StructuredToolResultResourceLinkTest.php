<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Tools\Concerns\StructuredToolResult;
use Mcp\Schema\Content\ResourceLink;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\RequestContext;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;

function structuredResultContext(bool $modern): RequestContext
{
    $request = new CallToolRequest('test', []);
    if ($modern) {
        $request = $request->withMeta(['io.modelcontextprotocol/protocolVersion' => '2026-07-28']);
    }

    return new RequestContext(new Session(new InMemorySessionStore(60)), $request);
}

function formatStructuredResult(RequestContext $context, array $payload): CallToolResult
{
    return (new class () {
        use StructuredToolResult;

        public function format(RequestContext $context, array $payload): CallToolResult
        {
            return $this->maybeStructuredResult($context, $payload);
        }
    })->format($context, $payload);
}

it('adds stable modern resource links without changing structured or text content', function (): void {
    $payload = [
        'schemaRefs' => ['kirby://fields/update-schema', 'kirby://field/{type}/update-schema'],
        'BEFORE_UPDATE_READ' => ['kirby://fields/update-schema', 'https://example.com/not-a-resource'],
        'schemaCheckReminder' => ['kirby://blueprint/page/update-schema'],
        'message' => 'Ignore arbitrary kirby://user/content text.',
    ];

    $result = formatStructuredResult(structuredResultContext(true), $payload);

    expect($result->structuredContent)->toBe($payload)
        ->and($result->content[0])->toBeInstanceOf(TextContent::class)
        ->and($result->content[0]->text)->toBe(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE))
        ->and(array_map(
            static fn ($link): ?string => $link instanceof ResourceLink ? $link->uri : null,
            array_slice($result->content, 1),
        ))
        ->toBe(['kirby://fields/update-schema', 'kirby://blueprint/page/update-schema']);
});

it('does not emit resource links for handshake-era clients', function (): void {
    $payload = ['schemaRefs' => ['kirby://fields/update-schema']];
    $result = formatStructuredResult(structuredResultContext(false), $payload);

    expect($result->structuredContent)->toBe($payload)
        ->and($result->content)->toHaveCount(1)
        ->and($result->content[0])->toBeInstanceOf(TextContent::class);
});

it('links concrete resource suggestion names and retains their known title', function (): void {
    $result = formatStructuredResult(structuredResultContext(true), [
        'suggestions' => [
            ['name' => 'kirby://kb', 'title' => 'Knowledge Base'],
            ['name' => 'kirby://kb/{path}', 'title' => 'Knowledge Base Document'],
            ['name' => 'kirby_search', 'title' => 'Search'],
        ],
    ]);

    expect($result->content)->toHaveCount(2)
        ->and($result->content[1])->toBeInstanceOf(ResourceLink::class)
        ->and($result->content[1]->uri)->toBe('kirby://kb')
        ->and($result->content[1]->title)->toBe('Knowledge Base');
});
