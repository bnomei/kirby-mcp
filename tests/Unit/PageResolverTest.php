<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Support\PageResolver;
use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Cms\Site;

final class PageResolverStubApp extends App
{
    /**
     * @param array<string, Page|null> $pages
     */
    public function __construct(private array $pages)
    {
    }

    public function page(string|null $id = null, Page|Site|null $parent = null, bool $drafts = true): ?Page
    {
        if ($id === null) {
            return null;
        }

        return $this->pages[$id] ?? null;
    }
}

it('resolves pages by id or page uuid fallback', function (): void {
    $home = new Page(['slug' => 'home']);
    $uuidPage = new Page(['slug' => 'uuid']);

    $app = new PageResolverStubApp([
        'home' => $home,
        'page://abc' => $uuidPage,
    ]);

    expect(PageResolver::resolve($app, null))->toBeNull();
    expect(PageResolver::resolve($app, '   '))->toBeNull();
    expect(PageResolver::resolve($app, ' home '))->toBe($home);
    expect(PageResolver::resolve($app, 'abc'))->toBe($uuidPage);
    expect(PageResolver::resolve($app, 'page://missing'))->toBeNull();
});
