<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
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
     *   kind: string,
     *   name: string,
     *   title: string,
     *   whenToUse: string,
     *   keywords: array<string, int>
     * }>
     */
    #[McpResource(
        uri: 'kirby://tools',
        name: 'tools',
        description: 'Weighted keyword index for Kirby MCP tools, resources, and resource templates (used by kirby_tool_suggest).',
        mimeType: 'application/json',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to fetch the full keyword index for Kirby MCP (tools + resources) so you can select the best next call without guessing.',
        keywords: [
            'tools' => 100,
            'resources' => 80,
            'index' => 80,
            'suggest' => 60,
            'keywords' => 40,
            'next' => 20,
        ],
    )]
    public function toolIndex(): array
    {
        return ToolIndex::all();
    }
}
