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

final class MarkdownDocsResourceConfiguredTtlStub extends AbstractMarkdownDocsResource
{
    public int $calls = 0;

    public function fetch(string $key, string $url): string
    {
        return $this->fetchCachedMarkdown($key, $url);
    }

    protected function httpGet(string $url, string $accept = 'application/json'): string
    {
        $this->calls++;
        return 'ok:' . $url;
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

it('can use a fixed docs ttl without reading project config', function (): void {
    $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-docs-ttl-' . bin2hex(random_bytes(6));
    $configDir = $root . DIRECTORY_SEPARATOR . '.kirby-mcp';
    mkdir($configDir, 0777, true);
    file_put_contents($configDir . DIRECTORY_SEPARATOR . 'mcp.json', '{"docs":{"ttlSeconds":0}}');

    putenv('KIRBY_MCP_PROJECT_ROOT=' . $root);
    StaticCache::set('cache:configured', 'cached', 60);

    try {
        $resource = new MarkdownDocsResourceConfiguredTtlStub(configuredDocsTtlSeconds: 60);

        expect($resource->fetch('cache:configured', 'https://example.test/doc'))->toBe('cached');
        expect($resource->calls)->toBe(0);
    } finally {
        putenv('KIRBY_MCP_PROJECT_ROOT');
        StaticCache::clearPrefix('cache:');
        @unlink($configDir . DIRECTORY_SEPARATOR . 'mcp.json');
        @rmdir($configDir);
        @rmdir($root);
    }
});
