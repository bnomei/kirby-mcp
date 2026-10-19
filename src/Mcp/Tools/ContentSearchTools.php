<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\RequestContext;

final class ContentSearchTools
{
    use Concerns\StructuredToolResult;

    public const PARENT = ['type' => ['object', 'null'], 'oneOf' => [...NativeContentTools::PARENT['oneOf'], ['type' => 'null']]];

    public function __construct(private readonly ProjectContext $context = new ProjectContext())
    {
    }

    #[McpTool(name: 'kirby_find_pages', title: 'Find Pages', description: 'Search permitted pages, including drafts. Omit parent for the whole site; explicit parent searches direct children unless recursive. Fixed filters combine with native relevance-ranked text search. Default language is the site default. Reuse identical filters with nextCursor.', outputSchema: NativeContentTools::OUTPUT)]
    #[McpToolIndex(whenToUse: 'Find pages by text, template or status.', keywords: ['find' => 100, 'pages' => 100, 'search' => 100])]
    #[Schema(properties: ['parent' => self::PARENT, 'status' => ['type' => 'string', 'enum' => ['all', 'draft', 'listed', 'unlisted', 'published']], 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100]])]
    public function pages(?array $parent = null, bool $recursive = false, ?string $template = null, string $status = 'all', ?string $language = null, ?string $query = null, int $limit = 20, ?string $cursor = null, ?RequestContext $requestContext = null): array|CallToolResult
    {
        return $this->maybeStructuredResult($requestContext, (new NativeContentTools($this->context))->run(['operation' => 'find_pages'] + compact('parent', 'recursive', 'template', 'status', 'language', 'query', 'limit', 'cursor'), $requestContext));
    }

    #[McpTool(name: 'kirby_find_files', title: 'Find Files', description: 'Search permitted site/page files. Omit parent for the whole site; explicit parent searches own files, plus descendants when recursive. Fixed filters combine with native text search. missingField matches only blueprint-defined metadata with an empty effective value. Default language is the site default. Reuse filters with nextCursor.', outputSchema: NativeContentTools::OUTPUT)]
    #[McpToolIndex(whenToUse: 'Find files by text, template, type or missing metadata.', keywords: ['find' => 100, 'files' => 100, 'search' => 100])]
    #[Schema(properties: ['parent' => self::PARENT, 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100]])]
    public function files(?array $parent = null, bool $recursive = false, ?string $template = null, ?string $type = null, ?string $missingField = null, ?string $language = null, ?string $query = null, int $limit = 20, ?string $cursor = null, ?RequestContext $requestContext = null): array|CallToolResult
    {
        return $this->maybeStructuredResult($requestContext, (new NativeContentTools($this->context))->run(['operation' => 'find_files'] + compact('parent', 'recursive', 'template', 'type', 'missingField', 'language', 'query', 'limit', 'cursor'), $requestContext));
    }
}
