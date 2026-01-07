<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Mcp\PromptIndex;
use Mcp\Exception\ResourceReadException;

final class PromptResources
{
    /**
     * List available MCP prompts (internal catalog; not registered with MCP).
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
    public function prompts(): array
    {
        return PromptIndex::all();
    }

    /**
     * Prompt details + rendered default messages (internal catalog; not registered with MCP).
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
