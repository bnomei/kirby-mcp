<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Resources\AbstractMarkdownDocsResource;
use Bnomei\KirbyMcp\Support\StaticCache;
use Mcp\Exception\ResourceReadException;

class MarkdownDocsResourceStub extends AbstractMarkdownDocsResource
{
    public int $calls = 0;

    public function fetch(string $key, string $url): string
    {
        return $this->fetchCachedMarkdown($key, $url);
    }

    protected function docsTtlSeconds(): int
    {
        return 60;
    }

    protected function httpGet(string $url, string $accept = 'application/json'): string
    {
        $this->calls++;
        return 'ok:' . $url;
    }
}

class MarkdownDocsResourceNoCacheStub extends MarkdownDocsResourceStub
{
    protected function docsTtlSeconds(): int
    {
        return 0;
    }
}

final class MarkdownDocsResourceErrorStub extends AbstractMarkdownDocsResource
{
    public function fetch(string $key, string $url): string
    {
        return $this->fetchCachedMarkdown($key, $url);
    }

    protected function docsTtlSeconds(): int
    {
        return 60;
    }

    protected function httpGet(string $url, string $accept = 'application/json'): string
    {
        throw new RuntimeException('boom');
    }
}

it('caches fetched markdown when ttl is enabled', function (): void {
    $resource = new MarkdownDocsResourceStub();

    $first = $resource->fetch('cache:key', 'https://example.test/doc');
    $second = $resource->fetch('cache:key', 'https://example.test/doc');

    expect($first)->toBe('ok:https://example.test/doc');
    expect($second)->toBe('ok:https://example.test/doc');
    expect($resource->calls)->toBe(1);
});

it('skips cache when ttl is disabled', function (): void {
    $resource = new MarkdownDocsResourceNoCacheStub();

    $first = $resource->fetch('cache:key', 'https://example.test/doc');
    $second = $resource->fetch('cache:key', 'https://example.test/doc');

    expect($first)->toBe('ok:https://example.test/doc');
    expect($second)->toBe('ok:https://example.test/doc');
    expect($resource->calls)->toBe(2);
});

it('wraps fetch errors in a ResourceReadException', function (): void {
    StaticCache::clear();
    $resource = new MarkdownDocsResourceErrorStub();

    expect(fn () => $resource->fetch('cache:error', 'https://example.test/doc'))
        ->toThrow(ResourceReadException::class, 'Failed to fetch https://example.test/doc: boom');
});
