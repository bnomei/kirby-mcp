<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\PromptIndex;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceReadException;
use Mcp\Schema\Annotations;
use Mcp\Schema\Enum\Role;

final class PromptResources
{
    /**
     * List available MCP prompts (fallback for clients without prompt support).
     *
     * @return array<int, array{
     *   name: string,
     *   description: string,
     *   args: array<int, array{name: string, type: string, required: bool, default: mixed, completion: null|array{values?: array<int, int|float|string>, enum?: string, providerClass?: string}}>,
     *   meta: null|array<string, mixed>,
     *   icons: null|array<int, array{src: string, mimeType?: string, sizes?: array<int, string>}>,
     *   generator: array{class: class-string, method: string},
     *   resource: string
     * }>
     */
    #[McpResource(
        uri: 'kirby://prompts',
        name: 'prompts',
        description: 'List MCP prompts with args/meta (fallback for clients without prompt support).',
        mimeType: 'application/json',
        annotations: new Annotations(
            audience: [Role::Assistant],
            priority: 0.3,
        ),
    )]
    #[McpToolIndex(
        whenToUse: 'Use to list available MCP prompts and their arguments (useful when the client does not support prompts natively).',
        keywords: [
            'prompts' => 100,
            'prompt' => 80,
            'workflow' => 40,
            'guided' => 30,
            'template' => 20,
        ],
    )]
    public function prompts(): array
    {
        return PromptIndex::all();
    }

    /**
     * Prompt details + rendered default messages (fallback for clients without prompt support).
     *
     * Note: if a prompt requires arguments without defaults, the prompt will be listed but may not render here.
     *
     * @return array{
     *   name: string,
     *   description: string,
     *   args: array<int, array{name: string, type: string, required: bool, default: mixed, completion: null|array{values?: array<int, int|float|string>, enum?: string, providerClass?: string}}>,
     *   meta: null|array<string, mixed>,
     *   icons: null|array<int, array{src: string, mimeType?: string, sizes?: array<int, string>}>,
     *   generator: array{class: class-string, method: string},
     *   resource: string,
     *   messages: null|array<int, array{role: string, content: string}>,
     *   renderError: null|string
     * }
     */
    #[McpResourceTemplate(
        uriTemplate: 'kirby://prompt/{name}',
        name: 'prompt',
        description: 'Prompt details + default rendered messages (fallback for clients without MCP prompt support).',
        mimeType: 'application/json',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to read one prompt’s details + default rendered messages via kirby://prompt/{name} (fallback for clients without prompt support).',
        keywords: [
            'prompt' => 100,
            'prompts' => 60,
            'workflow' => 40,
            'messages' => 30,
            'instructions' => 20,
        ],
    )]
    public function prompt(string $name): array
    {
        $name = trim(rawurldecode($name));
        $name = trim($name, '/');

        if ($name === '') {
            throw new ResourceReadException('Prompt name must not be empty.');
        }

        $prompt = PromptIndex::get($name);
        if ($prompt === null) {
            throw new ResourceReadException('Prompt not found: ' . $name);
        }

        $messages = null;
        $renderError = null;

        try {
            $messages = PromptIndex::renderMessages($name);
        } catch (\Throwable $exception) {
            $renderError = $exception->getMessage();
        }

        $prompt['messages'] = $messages;
        $prompt['renderError'] = $renderError;

        return $prompt;
    }
}
