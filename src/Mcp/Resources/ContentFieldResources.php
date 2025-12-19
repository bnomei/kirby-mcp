<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\Completion\PanelFieldTypeCompletionProvider;
use Bnomei\KirbyMcp\Mcp\Support\KbDocuments;
use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceReadException;

final class ContentFieldResources
{
    private const CONTENT_FIELDS_PREFIX = 'kb/content/fields/';

    #[McpResource(
        uri: 'kirby://fields/update-schema',
        name: 'update_schema_fields',
        description: 'List bundled content field update schemas (links to kirby://field/{type}/update-schema).',
        mimeType: 'text/markdown',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to browse bundled content field update schemas; open a field guide via kirby://field/{type}/update-schema.',
        keywords: [
            'content' => 100,
            'field' => 80,
            'fields' => 80,
            'storage' => 70,
            'update' => 60,
            'merge' => 60,
            'kb' => 40,
            'kirby_update_page_content' => 40,
        ],
    )]
    public function contentFieldsList(): string
    {
        $documents = KbDocuments::all();

        $types = [];
        foreach (array_keys($documents) as $relativePath) {
            if (!str_starts_with($relativePath, self::CONTENT_FIELDS_PREFIX) || !str_ends_with($relativePath, '.md')) {
                continue;
            }

            $type = basename($relativePath, '.md');
            if ($type === '') {
                continue;
            }

            $types[] = $type;
        }

        $types = array_values(array_unique($types));
        sort($types, SORT_STRING);

        $lines = [
            '# Kirby content field update schemas',
            '',
        ];

        foreach ($types as $type) {
            $lines[] = '- kirby://field/' . rawurlencode($type) . '/update-schema';
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    #[McpResourceTemplate(
        uriTemplate: 'kirby://field/{type}/update-schema',
        name: 'update_schema_field',
        description: 'Read a bundled content field update schema from kb/content/fields/{type}.md.',
        mimeType: 'text/markdown',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to read a single bundled content field guide (storage format, update payload, merge notes).',
        keywords: [
            'content' => 100,
            'field' => 80,
            'storage' => 80,
            'update' => 70,
            'merge' => 70,
            'payload' => 60,
            'kb' => 40,
            'kirby_update_page_content' => 40,
        ],
    )]
    public function contentField(
        #[CompletionProvider(provider: PanelFieldTypeCompletionProvider::class)]
        string $type,
    ): string {
        $type = $this->normalizeSlug($type, 'Field type');

        $path = self::CONTENT_FIELDS_PREFIX . $type . '.md';
        $documents = KbDocuments::all();

        $markdown = $documents[$path] ?? null;
        if (!is_string($markdown) || $markdown === '') {
            throw new ResourceReadException('Content field guide not found: ' . $type);
        }

        return $markdown;
    }

    private function normalizeSlug(string $value, string $label): string
    {
        $value = trim(rawurldecode($value));
        if ($value === '') {
            throw new ResourceReadException($label . ' must not be empty.');
        }

        $value = strtolower(trim($value, '/'));

        if (preg_match('/^[a-z0-9][a-z0-9-]*$/u', $value) !== 1) {
            throw new ResourceReadException($label . ' must be a slug (a-z, 0-9, dash).');
        }

        return $value;
    }
}
