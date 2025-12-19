<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Docs\ExtensionReferenceIndex;
use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\Completion\ExtensionNameCompletionProvider;
use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceReadException;

final class ExtensionReferenceResources extends AbstractMarkdownDocsResource
{
    private const BASE_URL = 'https://getkirby.com/docs/reference/plugins/extensions';

    #[McpResource(
        uri: 'kirby://extensions',
        name: 'extensions',
        description: 'List Kirby plugin extensions (links to kirby://extension/{name}).',
        mimeType: 'text/markdown',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to list Kirby plugin extension points, then open a specific extension reference via kirby://extension/{name}.',
        keywords: [
            'extension' => 100,
            'extensions' => 80,
            'plugin' => 60,
            'plugins' => 60,
            'reference' => 60,
            'docs' => 40,
            'registry' => 30,
        ],
    )]
    public function extensionsList(): string
    {
        $lines = [
            '# Kirby extensions',
            '',
            'Source: <' . self::BASE_URL . '>',
            '',
        ];

        foreach (ExtensionReferenceIndex::EXTENSIONS as $slug => $label) {
            $lines[] = '- [' . $label . '](kirby://extension/' . $slug . ')';
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    #[McpResourceTemplate(
        uriTemplate: 'kirby://extension/{name}',
        name: 'extension',
        description: 'Fetch Kirby extension reference markdown from getkirby.com (docs/reference/plugins/extensions/{slug}).',
        mimeType: 'text/markdown',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to read official docs for a specific Kirby plugin extension (API, examples, options).',
        keywords: [
            'extension' => 100,
            'plugin' => 60,
            'plugins' => 60,
            'reference' => 60,
            'docs' => 40,
            'options' => 30,
            'api' => 30,
        ],
    )]
    public function extension(
        #[CompletionProvider(provider: ExtensionNameCompletionProvider::class)]
        string $name,
    ): string {
        $slug = $this->normalizeExtensionSlug($name);
        $markdownUrl = self::BASE_URL . '/' . $slug . '.md';

        return $this->fetchCachedMarkdown('docs:extensions:' . $slug, $markdownUrl);
    }

    private function normalizeExtensionSlug(string $value): string
    {
        $value = trim(rawurldecode($value));
        $value = trim($value, '/');

        if ($value === '') {
            throw new ResourceReadException('Extension name must not be empty.');
        }

        if (str_contains($value, '/')) {
            throw new ResourceReadException('Extension name must not contain slashes.');
        }

        if (preg_match('/^[A-Za-z0-9_-]+$/u', $value) !== 1) {
            throw new ResourceReadException('Extension name contains invalid characters.');
        }

        $slug = preg_replace('/([a-z0-9])([A-Z])/u', '$1-$2', $value) ?? $value;
        $slug = str_replace('_', '-', $slug);
        $slug = strtolower($slug);
        $slug = preg_replace('/-+/u', '-', $slug) ?? $slug;
        $slug = trim($slug, '-');

        if (preg_match('/^[a-z0-9][a-z0-9-]*$/u', $slug) !== 1) {
            throw new ResourceReadException('Extension name must resolve to a slug (a-z, 0-9, dash).');
        }

        return $slug;
    }
}
