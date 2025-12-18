<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Docs\KirbyDocsUrl;

it('converts official docs object ids to crawl-friendly markdown URLs', function (): void {
    $urls = KirbyDocsUrl::fromObjectId('docs/reference');

    expect($urls['htmlUrl'])->toBe('https://getkirby.com/docs/reference');
    expect($urls['markdownUrl'])->toBe('https://getkirby.com/docs/reference.md');
    expect($urls['crawlUrl'])->toBe('https://getkirby.com/docs/reference.md');
});

it('does not append .md for non-docs object ids', function (): void {
    $urls = KirbyDocsUrl::fromObjectId('plugins/tobimori/seo');

    expect($urls['htmlUrl'])->toBe('https://getkirby.com/plugins/tobimori/seo');
    expect($urls['markdownUrl'])->toBeNull();
    expect($urls['crawlUrl'])->toBe('https://getkirby.com/plugins/tobimori/seo');
});
