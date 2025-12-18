<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Mcp\ToolIndex;
use Mcp\Capability\Attribute\McpResource;

final class MetaResources
{
    /**
     * Weighted keyword index used by `kirby_tool_suggest`.
     *
     * This is useful as a static resource so clients can keep it in context and
     * select tools without trial-and-error.
     *
     * @return array<int, array{
     *   name: string,
     *   title: string,
     *   whenToUse: string,
     *   keywords: array<string, int>
     * }>
     */
    #[McpResource(
        uri: 'kirby://meta/tool-index',
        name: 'tool_index',
        description: 'Weighted keyword index for Kirby MCP tools (used by kirby_tool_suggest).',
        mimeType: 'application/json',
    )]
    public function toolIndex(): array
    {
        return ToolIndex::all();
    }
}
