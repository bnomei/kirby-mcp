<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Docs\HookReferenceIndex;
use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\Completion\HookNameCompletionProvider;
use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceReadException;
use Mcp\Schema\Annotations;
use Mcp\Schema\Enum\Role;

final class HookReferenceResources extends AbstractMarkdownDocsResource
{
    private const BASE_URL = 'https://getkirby.com/docs/reference/plugins/hooks';

    #[McpResource(
        uri: 'kirby://hooks',
        name: 'hooks',
        description: 'List Kirby plugin hook names (links to kirby://hook/{name}).',
        mimeType: 'text/markdown',
        annotations: new Annotations(
            audience: [Role::Assistant],
            priority: 0.4,
        ),
    )]
    #[McpToolIndex(
        whenToUse: 'Use to list Kirby hook names, then open a specific hook reference via kirby://hook/{name}.',
        keywords: [
            'hook' => 100,
            'hooks' => 80,
            'plugin' => 60,
            'plugins' => 60,
            'events' => 40,
            'reference' => 60,
            'docs' => 40,
        ],
    )]
    public function hooksList(): string
    {
        $lines = [
            '# Kirby hooks',
            '',
            'Source: <' . self::BASE_URL . '>',
            '',
        ];

        foreach (HookReferenceIndex::HOOKS as $hook) {
            $lines[] = '- [' . $hook . '](kirby://hook/' . $hook . ')';
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    #[McpResourceTemplate(
        uriTemplate: 'kirby://hook/{name}',
        name: 'hook',
        description: 'Fetch Kirby hook reference markdown from getkirby.com (docs/reference/plugins/hooks/{slug}). Accepts hook names like file.changeName:after or slugs like file-changename-after.',
        mimeType: 'text/markdown',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to read official docs for a Kirby hook (timing, params, examples).',
        keywords: [
            'hook' => 100,
            'event' => 60,
            'events' => 60,
            'plugin' => 40,
            'reference' => 60,
            'docs' => 40,
            'after' => 20,
            'before' => 20,
        ],
    )]
    public function hook(
        #[CompletionProvider(provider: HookNameCompletionProvider::class)]
        string $name,
    ): string {
        $slug = $this->normalizeHookSlugOrName($name);
        $markdownUrl = self::BASE_URL . '/' . $slug . '.md';

        return $this->fetchCachedMarkdown('docs:hooks:' . $slug, $markdownUrl);
    }

    private function normalizeHookSlugOrName(string $value): string
    {
        $value = trim(rawurldecode($value));
        $value = trim($value, '/');

        if ($value === '') {
            throw new ResourceReadException('Hook name must not be empty.');
        }

        $slug = $value;

        if (str_contains($value, '.') || str_contains($value, ':')) {
            if (preg_match('/^[A-Za-z0-9._:-]+$/u', $value) !== 1) {
                throw new ResourceReadException('Hook name contains invalid characters.');
            }

            $slug = str_replace(['.', ':'], '-', $value);
        }

        $slug = strtolower($slug);

        if (preg_match('/^[a-z0-9][a-z0-9-]*$/u', $slug) !== 1) {
            throw new ResourceReadException('Hook name must resolve to a slug (a-z, 0-9, dash).');
        }

        return $slug;
    }
}
