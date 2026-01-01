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

final class KbResources
{
    private const KB_PREFIX = 'kb/';

    #[McpResource(
        uri: 'kirby://kb',
        name: 'kb',
        description: 'List bundled KB documents (links to kirby://kb/{path}). Paths are relative to kb/ and omit .md.',
        mimeType: 'text/markdown',
        annotations: new Annotations(
            audience: [Role::Assistant],
            priority: 0.3,
        ),
    )]
    #[McpToolIndex(
        whenToUse: 'Use to browse all bundled KB documents (scenarios, panel docs, glossary, update schema) and open a file via kirby://kb/{path}.',
        keywords: [
            'kb' => 100,
            'knowledge' => 80,
            'index' => 60,
            'list' => 40,
            'kirby' => 40,
            'docs' => 30,
        ],
    )]
    public function kbList(): string
    {
        $documents = KbDocuments::all();

        $entries = [];
        foreach ($documents as $relative => $contents) {
            if (!str_starts_with($relative, self::KB_PREFIX) || !str_ends_with($relative, '.md')) {
                continue;
            }

            $path = substr($relative, strlen(self::KB_PREFIX));
            $path = substr($path, 0, -3);
            if ($path === '') {
                continue;
            }

            $entries[] = [
                'path' => $path,
                'size' => strlen($contents),
            ];
        }

        usort($entries, static function (array $a, array $b): int {
            return strcmp((string)($a['path'] ?? ''), (string)($b['path'] ?? ''));
        });

        $lines = [
            '# Kirby KB index',
            '',
            'Paths are relative to kb/ and omit the .md extension.',
            'Total: ' . count($entries) . ' documents.',
            '',
        ];

        foreach ($entries as $entry) {
            $path = (string)($entry['path'] ?? '');
            $size = (int)($entry['size'] ?? 0);
            if ($path === '') {
                continue;
            }

            $lines[] = '- kirby://kb/' . $path . ' (size: ' . $size . ' bytes)';
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    #[McpResourceTemplate(
        uriTemplate: 'kirby://kb/{path}',
        name: 'kb_document',
        description: 'Read a bundled KB document from kb/{path}.md (path relative to kb/, no .md).',
        mimeType: 'text/markdown',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to read a single bundled KB document by path (no network).',
        keywords: [
            'kb' => 100,
            'knowledge' => 80,
            'document' => 60,
            'read' => 40,
            'kirby' => 30,
        ],
    )]
    public function document(string $path): string
    {
        $path = trim(rawurldecode($path));
        $path = trim($path, '/');

        if ($path === '') {
            throw new ResourceReadException('KB path must not be empty.');
        }

        if (str_starts_with($path, self::KB_PREFIX)) {
            $path = substr($path, strlen(self::KB_PREFIX));
            $path = trim($path, '/');
        }

        if (str_ends_with(strtolower($path), '.md')) {
            $path = substr($path, 0, -3);
        }

        if ($path === '' || str_contains($path, '..') || str_contains($path, '\\')) {
            throw new ResourceReadException('KB path is invalid.');
        }

        if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._\\/-]*$/u', $path) !== 1) {
            throw new ResourceReadException('KB path must be a safe path (letters, numbers, dot, dash, slash).');
        }

        $relative = self::KB_PREFIX . $path . '.md';
        $documents = KbDocuments::all();

        $markdown = $documents[$relative] ?? null;
        if (!is_string($markdown) || $markdown === '') {
            throw new ResourceReadException('KB document not found: ' . $path);
        }

        return $markdown;
    }
}
