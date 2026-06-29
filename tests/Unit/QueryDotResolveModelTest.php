<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Commands\QueryDot;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\Page;
use Kirby\Cms\Site;

final class QueryDotResolveModelStubApp extends App
{
    /**
     * @param array<string, Page|null> $pages
     * @param array<string, File|null> $files
     */
    public function __construct(private array $pages, private array $files = [])
    {
    }

    public function page(string|null $id = null, Page|Site|null $parent = null, bool $drafts = true): ?Page
    {
        if ($id === null) {
            return null;
        }

        return $this->pages[$id] ?? null;
    }

    public function file(string|null $path = null, mixed $parent = null, bool $drafts = true): ?File
    {
        if ($path === null) {
            return null;
        }

        return $this->files[$path] ?? null;
    }
}

function peekExceptionHandler(): ?callable
{
    $handler = set_exception_handler(null);
    restore_exception_handler();

    return $handler;
}

function queryDotResolveModel(App $app, string $modelArg): mixed
{
    $method = new ReflectionMethod(QueryDot::class, 'resolveModel');

    $errorHandlers = captureErrorHandlers();
    $baselineExceptionHandler = peekExceptionHandler();

    try {
        return $method->invoke(null, $app, $modelArg);
    } finally {
        restoreErrorHandlers($errorHandlers);

        while (peekExceptionHandler() !== $baselineExceptionHandler) {
            restore_exception_handler();
        }
    }
}

it('resolves dotted page slugs as pages rather than files', function (): void {
    $page = new Page(['slug' => 'release-2-0']);

    $app = new QueryDotResolveModelStubApp(
        pages: ['release-2.0' => $page],
    );

    expect(queryDotResolveModel($app, 'release-2.0'))->toBe($page);
});

it('falls back to file resolution when no page matches a dotted id', function (): void {
    $file = new File(['filename' => 'cover.jpg', 'parent' => new Page(['slug' => 'blog'])]);

    $app = new QueryDotResolveModelStubApp(
        pages: [],
        files: ['blog/cover.jpg' => $file],
    );

    expect(queryDotResolveModel($app, 'blog/cover.jpg'))->toBe($file);
});
