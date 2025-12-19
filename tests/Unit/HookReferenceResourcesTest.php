<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Resources\HookReferenceResources;
use Bnomei\KirbyMcp\Support\StaticCache;
use Mcp\Exception\ResourceReadException;

it('lists Kirby hook resources', function (): void {
    $resource = new HookReferenceResources();
    $markdown = $resource->hooksList();

    expect($markdown)->toContain('kirby://hook/file.changeName:after');
    expect($markdown)->toContain('kirby://hook/system.exception');
});

it('supports hook name and slug inputs', function (): void {
    $resource = new HookReferenceResources();

    $slug = 'file-changename-after';
    $cacheKey = 'docs:hooks:' . $slug;

    StaticCache::set($cacheKey, "# cached\n", 60);

    try {
        expect($resource->hook('file.changeName:after'))->toBe("# cached\n");
        expect($resource->hook('file-changename-after'))->toBe("# cached\n");
    } finally {
        StaticCache::clearPrefix('docs:hooks:');
    }
});

it('validates hook inputs', function (): void {
    $resource = new HookReferenceResources();

    expect(fn () => $resource->hook('bad slug'))->toThrow(ResourceReadException::class);
    expect(fn () => $resource->hook('../system-exception'))->toThrow(ResourceReadException::class);
});
