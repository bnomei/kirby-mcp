<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Resources\GlossaryResources;
use Bnomei\KirbyMcp\Mcp\Support\KbDocuments;
use Bnomei\KirbyMcp\Support\StaticCache;
use Mcp\Exception\ResourceReadException;

it('lists bundled Kirby glossary entries as MCP resources', function (): void {
    $resource = new GlossaryResources();
    $markdown = $resource->glossaryList();

    expect($markdown)->toContain('kirby://glossary/api');
    expect($markdown)->toContain('kirby://glossary/kql');
    expect($markdown)->not()->toContain('kirby://glossary/PLAN');
});

it('reads a bundled glossary entry by term', function (): void {
    $resource = new GlossaryResources();
    $markdown = $resource->term('api');

    $expected = file_get_contents(dirname(__DIR__, 2) . '/kb/glossary/api.md');
    expect($expected)->toBeString()->not()->toBe('');

    expect($markdown)->toBe($expected);
});

it('validates glossary terms as slugs', function (): void {
    $resource = new GlossaryResources();

    expect(fn () => $resource->term('bad slug'))->toThrow(ResourceReadException::class);
    expect(fn () => $resource->term('../api'))->toThrow(ResourceReadException::class);
});

it('shares the KB document cache with the kb search tool', function (): void {
    StaticCache::clear();

    $resource = new GlossaryResources();
    $resource->glossaryList();

    expect(StaticCache::get(KbDocuments::CACHE_KEY))->toBeArray();
});
