<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Resources\ExtensionReferenceResources;
use Bnomei\KirbyMcp\Support\StaticCache;
use Mcp\Exception\ResourceReadException;

it('lists Kirby extension resources', function (): void {
    $resource = new ExtensionReferenceResources();
    $markdown = $resource->extensionsList();

    expect($markdown)->toContain('kirby://extension/commands');
    expect($markdown)->toContain('kirby://extension/routes');
    expect($markdown)->toContain('kirby://extension/darkroom-drivers');
});

it('supports cached extension markdown fetches', function (): void {
    $resource = new ExtensionReferenceResources();

    StaticCache::set('docs:extensions:commands', "# cached\n", 60);
    StaticCache::set('docs:extensions:panel-view-buttons', "# cached-panel\n", 60);

    try {
        expect($resource->extension('commands'))->toBe("# cached\n");
        expect($resource->extension('panelViewButtons'))->toBe("# cached-panel\n");
    } finally {
        StaticCache::clearPrefix('docs:extensions:');
    }
});

it('validates extension inputs', function (): void {
    $resource = new ExtensionReferenceResources();

    expect(fn () => $resource->extension(''))->toThrow(ResourceReadException::class);
    expect(fn () => $resource->extension('bad slug'))->toThrow(ResourceReadException::class);
    expect(fn () => $resource->extension('../routes'))->toThrow(ResourceReadException::class);
});
