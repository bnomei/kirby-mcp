<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Docs\PanelReferenceIndex;
use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceReadException;
use Bnomei\KirbyMcp\Mcp\Completion\PanelFieldTypeCompletionProvider;
use Bnomei\KirbyMcp\Mcp\Completion\PanelSectionTypeCompletionProvider;

final class PanelReferenceResources extends AbstractMarkdownDocsResource
{
    private const BASE_URL = 'https://getkirby.com/docs/reference/panel';

    #[McpResource(
        uri: 'kirby://fields',
        name: 'panel_fields',
        description: 'List Kirby Panel field types (links to kirby://field/{type}).',
        mimeType: 'text/markdown',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to list Kirby Panel field types, then open a specific field reference via kirby://field/{type} (e.g. kirby://field/text).',
        keywords: [
            'panel' => 80,
            'field' => 100,
            'fields' => 80,
            'reference' => 60,
            'docs' => 40,
            'options' => 40,
            'settings' => 40,
            'type' => 30,
        ],
    )]
    public function fieldsList(): string
    {
        $lines = [
            '# Kirby Panel fields',
            '',
            'Source: <' . self::BASE_URL . '/fields>',
            '',
        ];

        foreach (PanelReferenceIndex::FIELD_TYPES as $type => $label) {
            $lines[] = '- [' . $label . '](kirby://field/' . $type . ')';
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    #[McpResource(
        uri: 'kirby://sections',
        name: 'panel_sections',
        description: 'List Kirby Panel section types (links to kirby://section/{type}).',
        mimeType: 'text/markdown',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to list Kirby Panel section types, then open a specific section reference via kirby://section/{type}.',
        keywords: [
            'panel' => 80,
            'section' => 100,
            'sections' => 80,
            'reference' => 60,
            'docs' => 40,
            'options' => 40,
            'settings' => 40,
            'type' => 30,
        ],
    )]
    public function sectionsList(): string
    {
        $lines = [
            '# Kirby Panel sections',
            '',
            'Source: <' . self::BASE_URL . '/sections>',
            '',
        ];

        foreach (PanelReferenceIndex::SECTION_TYPES as $type => $label) {
            $lines[] = '- [' . $label . '](kirby://section/' . $type . ')';
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    #[McpResourceTemplate(
        uriTemplate: 'kirby://field/{type}',
        name: 'panel_field',
        description: 'Fetch Kirby Panel field reference markdown from getkirby.com (docs/reference/panel/fields/{type}).',
        mimeType: 'text/markdown',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to read official reference for a Kirby Panel field type (props/options, defaults, examples).',
        keywords: [
            'panel' => 80,
            'field' => 100,
            'reference' => 80,
            'docs' => 60,
            'options' => 70,
            'settings' => 70,
            'props' => 60,
            'properties' => 60,
            'property' => 40,
            'text' => 40,
            'type' => 30,
        ],
    )]
    public function field(
        #[CompletionProvider(provider: PanelFieldTypeCompletionProvider::class)]
        string $type,
    ): string {
        $type = $this->normalizeSlug($type, 'Field type');
        $markdownUrl = self::BASE_URL . '/fields/' . $type . '.md';

        return $this->fetchCachedMarkdown('docs:panel:field:' . $type, $markdownUrl);
    }

    #[McpResourceTemplate(
        uriTemplate: 'kirby://section/{type}',
        name: 'panel_section',
        description: 'Fetch Kirby Panel section reference markdown from getkirby.com (docs/reference/panel/sections/{type}).',
        mimeType: 'text/markdown',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to read official reference for a Kirby Panel section type (options, examples).',
        keywords: [
            'panel' => 80,
            'section' => 100,
            'reference' => 80,
            'docs' => 60,
            'options' => 70,
            'settings' => 70,
            'props' => 60,
            'properties' => 60,
            'property' => 40,
            'type' => 30,
        ],
    )]
    public function section(
        #[CompletionProvider(provider: PanelSectionTypeCompletionProvider::class)]
        string $type,
    ): string {
        $type = $this->normalizeSlug($type, 'Section type');
        $markdownUrl = self::BASE_URL . '/sections/' . $type . '.md';

        return $this->fetchCachedMarkdown('docs:panel:section:' . $type, $markdownUrl);
    }

    private function normalizeSlug(string $value, string $label): string
    {
        $value = trim(rawurldecode($value));
        if ($value === '') {
            throw new ResourceReadException($label . ' must not be empty.');
        }

        $value = strtolower($value);
        $value = trim($value, '/');

        if (preg_match('/^[a-z0-9][a-z0-9-]*$/u', $value) !== 1) {
            throw new ResourceReadException($label . ' must be a slug (a-z, 0-9, dash).');
        }

        return $value;
    }
}
