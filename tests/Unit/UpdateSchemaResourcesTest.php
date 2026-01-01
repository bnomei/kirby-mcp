<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Resources\UpdateSchemaResources;
use Bnomei\KirbyMcp\Mcp\Support\KbDocuments;
use Bnomei\KirbyMcp\Support\StaticCache;
use Mcp\Exception\ResourceReadException;

it('lists bundled Kirby content field guides as MCP resources', function (): void {
    $resource = new UpdateSchemaResources();
    $markdown = $resource->contentFieldsList();

    expect($markdown)->toContain('kirby://field/text/update-schema');
    expect($markdown)->toContain('kirby://field/blocks/update-schema');
    expect($markdown)->not()->toContain('kirby://field/blueprint-page/update-schema');
    expect($markdown)->not()->toContain('kirby://field/PLAN/update-schema');
});

it('reads a bundled content field guide by type', function (): void {
    $resource = new UpdateSchemaResources();
    $markdown = $resource->contentField('text');

    $expected = file_get_contents(dirname(__DIR__, 2) . '/kb/update-schema/text.md');
    expect($expected)->toBeString()->not()->toBe('');

    expect($markdown)->toBe($expected);
});

it('lists bundled Kirby blueprint update guides as MCP resources', function (): void {
    $resource = new UpdateSchemaResources();
    $markdown = $resource->blueprintUpdateSchemasList();

    expect($markdown)->toContain('kirby://blueprint/page/update-schema');
    expect($markdown)->toContain('kirby://blueprint/site/update-schema');
});

it('reads a bundled blueprint update guide by type', function (): void {
    $resource = new UpdateSchemaResources();
    $markdown = $resource->blueprintUpdateSchema('page');

    $expected = file_get_contents(dirname(__DIR__, 2) . '/kb/update-schema/blueprint-page.md');
    expect($expected)->toBeString()->not()->toBe('');

    expect($markdown)->toBe($expected);
});

it('validates content field types as slugs', function (): void {
    $resource = new UpdateSchemaResources();

    expect(fn () => $resource->contentField('bad slug'))->toThrow(ResourceReadException::class);
    expect(fn () => $resource->contentField('../text'))->toThrow(ResourceReadException::class);
});

it('shares the KB document cache with the kb search tool', function (): void {
    StaticCache::clear();

    $resource = new UpdateSchemaResources();
    $resource->contentFieldsList();

    expect(StaticCache::get(KbDocuments::CACHE_KEY))->toBeArray();
});
