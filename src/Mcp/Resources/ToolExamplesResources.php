<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Schema\Annotations;
use Mcp\Schema\Enum\Role;

final class ToolExamplesResources
{
    /**
     * Curated examples for Kirby MCP tools that are easy to misuse.
     *
     * @return array<int, array{
     *   tool: string,
     *   description: string,
     *   examples: array<int, array{summary: string, args: array<string, mixed>}>,
     *   notes?: string
     * }>
     */
    #[McpResource(
        uri: 'kirby://tool-examples',
        name: 'tool-examples',
        description: 'Curated usage examples for Kirby MCP tools with stricter inputs or confirm flows.',
        mimeType: 'application/json',
        annotations: new Annotations(
            audience: [Role::Assistant],
            priority: 0.5,
        ),
    )]
    #[McpToolIndex(
        whenToUse: 'Use to see safe, copy-ready examples for Kirby MCP tools that require confirmations or strict input shapes.',
        keywords: [
            'examples' => 120,
            'tool' => 80,
            'tools' => 60,
            'usage' => 60,
            'confirm' => 50,
            'input' => 40,
            'update' => 30,
            'cli' => 30,
        ],
    )]
    public function toolExamples(): array
    {
        return [
            [
                'tool' => 'kirby_update_page_content',
                'description' => 'Two-step update flow with preview then confirm.',
                'examples' => [
                    [
                        'summary' => 'Preview updates (confirm=false).',
                        'args' => [
                            'id' => 'home',
                            'data' => [
                                'title' => 'Hello with AI',
                                'intro' => 'Short summary text.',
                            ],
                            'payloadValidatedWithFieldSchemas' => true,
                            'confirm' => false,
                            'validate' => true,
                        ],
                    ],
                    [
                        'summary' => 'Apply updates (confirm=true).',
                        'args' => [
                            'id' => 'home',
                            'data' => [
                                'title' => 'Hello with AI',
                                'intro' => 'Short summary text.',
                            ],
                            'payloadValidatedWithFieldSchemas' => true,
                            'confirm' => true,
                            'validate' => true,
                        ],
                    ],
                ],
                'notes' => 'The data argument must be a JSON object mapping field keys to values (not an array). Pass the object directly; a JSON-encoded string is accepted for compatibility. Read kirby://field/{type}/update-schema first, then set payloadValidatedWithFieldSchemas=true.',
            ],
            [
                'tool' => 'kirby_run_cli_command',
                'description' => 'Run a safe, read-only Kirby CLI command.',
                'examples' => [
                    [
                        'summary' => 'Inspect a command help page.',
                        'args' => [
                            'command' => 'help',
                            'arguments' => ['backup'],
                            'allowWrite' => false,
                        ],
                    ],
                    [
                        'summary' => 'List available commands (read-only).',
                        'args' => [
                            'command' => 'help',
                            'arguments' => [],
                            'allowWrite' => false,
                        ],
                    ],
                ],
                'notes' => 'Prefer kirby://commands and kirby://cli/command/{command} for discovery and usage details.',
            ],
        ];
    }
}
