<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\Support\KbDocuments;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceReadException;
use Mcp\Schema\Annotations;
use Mcp\Schema\Enum\Role;

final class GlossaryResources
{
    private const GLOSSARY_PREFIX = 'kb/kirby/glossary/';

    #[McpResource(
        uri: 'kirby://glossary',
        name: 'glossary',
        description: 'List bundled Kirby glossary terms (links to kirby://glossary/{term}).',
        mimeType: 'text/markdown',
        annotations: new Annotations(
            audience: [Role::Assistant],
            priority: 0.4,
        ),
    )]
    #[McpToolIndex(
        whenToUse: 'Use to browse bundled Kirby glossary terms (quick local definitions); open a term via kirby://glossary/{term}.',
        keywords: [
            'glossary' => 100,
            'terms' => 60,
            'definition' => 60,
            'explain' => 40,
            'kb' => 40,
            'kirby' => 30,
        ],
    )]
    public function glossaryList(): string
    {
        $documents = KbDocuments::all();

        $terms = [];
        foreach (array_keys($documents) as $relativePath) {
            if (!str_starts_with($relativePath, self::GLOSSARY_PREFIX) || !str_ends_with($relativePath, '.md')) {
                continue;
            }

            $term = basename($relativePath, '.md');
            if ($term === '') {
                continue;
            }

            $terms[] = $term;
        }

        $terms = array_values(array_unique($terms));
        sort($terms, SORT_STRING);

        $lines = [
            '# Kirby glossary',
            '',
        ];

        foreach ($terms as $term) {
            $lines[] = '- kirby://glossary/' . rawurlencode($term);
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    #[McpResourceTemplate(
        uriTemplate: 'kirby://glossary/{term}',
        name: 'glossary_term',
        description: 'Read a bundled Kirby glossary entry from kb/kirby/glossary/{term}.md.',
        mimeType: 'text/markdown',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to read a single bundled Kirby glossary entry by term (fast local context; no network).',
        keywords: [
            'glossary' => 100,
            'term' => 60,
            'definition' => 60,
            'explain' => 40,
            'kb' => 40,
            'kirby' => 30,
        ],
    )]
    public function term(string $term): string
    {
        $term = trim(rawurldecode($term));
        $term = trim($term, '/');

        if ($term === '') {
            throw new ResourceReadException('Glossary term must not be empty.');
        }

        if (str_ends_with(strtolower($term), '.md')) {
            $term = substr($term, 0, -3);
        }

        $term = strtolower($term);

        if (preg_match('/^[a-z0-9][a-z0-9-]*$/u', $term) !== 1) {
            throw new ResourceReadException('Glossary term must be a slug (a-z, 0-9, dash).');
        }

        $path = self::GLOSSARY_PREFIX . $term . '.md';
        $documents = KbDocuments::all();

        $markdown = $documents[$path] ?? null;
        if (!is_string($markdown) || $markdown === '') {
            throw new ResourceReadException('Glossary entry not found: ' . $term);
        }

        return $markdown;
    }
}
