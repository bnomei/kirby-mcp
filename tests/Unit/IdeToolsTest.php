<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Tools\IdeTools;

/**
 * @param array<string, string> $templates
 * @param array<string, string> $snippets
 */
function withIdeToolsStatusProject(array $templates, array $snippets, callable $callback): mixed
{
    $previousRoot = getenv(ProjectContext::ENV_PROJECT_ROOT);

    $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-ide-status-' . bin2hex(random_bytes(6));
    mkdir($root . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'templates', 0777, true);
    mkdir($root . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'snippets', 0777, true);

    ideToolsWritePhpFiles($root . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'templates', $templates);
    ideToolsWritePhpFiles($root . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'snippets', $snippets);

    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $root);

    try {
        return $callback($root);
    } finally {
        if ($previousRoot === false) {
            putenv(ProjectContext::ENV_PROJECT_ROOT);
        } else {
            putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $previousRoot);
        }

        ideToolsRemoveDirectory($root);
    }
}

/**
 * @param array<string, string> $files
 */
function ideToolsWritePhpFiles(string $root, array $files): void
{
    foreach ($files as $relativePath => $contents) {
        $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Failed to write test file: ' . $path);
        }
    }
}

function ideToolsRemoveDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isDir()) {
            @rmdir($file->getPathname());
        } else {
            @unlink($file->getPathname());
        }
    }

    @rmdir($dir);
}

it('reports IDE helper status', function (): void {
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $status = (new IdeTools())->ideHelpersStatus(withDetails: false, limit: 5);

    expect($status)->toBeArray();
    expect($status)->toHaveKeys([
        'projectRoot',
        'host',
        'watchedInputs',
        'inputs',
        'helpers',
        'templates',
        'snippets',
        'controllers',
        'pageModels',
        'recommendations',
        'notes',
    ]);
    expect($status['templates'])->toHaveKeys(['total', 'withKirbyVarHints', 'missingKirbyVarHints', 'missing']);
    expect($status['snippets'])->toHaveKeys(['total', 'withKirbyVarHints', 'missingKirbyVarHints', 'missing']);
    expect($status['controllers'])->toHaveKeys(['total', 'closureControllers', 'withKirbyTypeHints', 'missingKirbyTypeHints', 'missing']);
    expect($status['pageModels'])->toHaveKeys(['total', 'pageModelFiles', 'withKirbyTypeHints', 'missingKirbyTypeHints', 'missing']);
});

it('plans IDE helper generation in dry-run mode', function (): void {
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $result = (new IdeTools())->generateIdeHelpers(dryRun: true, force: false, preferRuntime: true);

    expect($result)->toBeArray();
    expect($result['dryRun'])->toBeTrue();
    expect($result)->toHaveKeys([
        'ok',
        'dryRun',
        'projectRoot',
        'outputDir',
        'source',
        'files',
        'stats',
    ]);
    expect($result['files'])->toBeArray();
    expect(count($result['files']))->toBeGreaterThanOrEqual(1);
});

it('does not report missing template var hints when Kirby globals are not used', function (): void {
    withIdeToolsStatusProject([
        'plain.php' => <<<'PHP'
<p>No Kirby globals are used.</p>
<?php
// $page in a comment does not require a hint.
/** $site in a docblock does not require a hint. */
echo 'ok';
PHP,
    ], [], function (): void {
        $status = (new IdeTools())->ideHelpersStatus(withDetails: true, limit: 5);

        expect($status['templates']['total'])->toBe(1)
            ->and($status['templates']['withKirbyVarHints'])->toBe(0)
            ->and($status['templates']['missingKirbyVarHints'])->toBe(0)
            ->and($status['templates']['missing'])->toBe([])
            ->and(implode("\n", $status['recommendations']))->not->toContain('Add Kirby template/snippet PHPDoc @var hints');
    });
});

it('reports a used template global without a matching var hint', function (): void {
    withIdeToolsStatusProject([
        'default.php' => <<<'PHP'
<?php
echo $page->title();
PHP,
    ], [], function (): void {
        $status = (new IdeTools())->ideHelpersStatus(withDetails: true, limit: 5);

        expect($status['templates']['missingKirbyVarHints'])->toBe(1)
            ->and($status['templates']['missing'])->toHaveCount(1)
            ->and($status['templates']['missing'][0])->toMatchArray([
                'id' => 'default',
                'relativePath' => 'site/templates/default.php',
                'missingVars' => ['$page'],
            ]);
    });
});

it('accepts a matching template var hint for a used global', function (): void {
    withIdeToolsStatusProject([
        'default.php' => <<<'PHP'
<?php
/** @var Kirby\Cms\Page $page */
echo $page->title();
PHP,
    ], [], function (): void {
        $status = (new IdeTools())->ideHelpersStatus(withDetails: true, limit: 5);

        expect($status['templates']['withKirbyVarHints'])->toBe(1)
            ->and($status['templates']['missingKirbyVarHints'])->toBe(0)
            ->and($status['templates']['missing'])->toBe([]);
    });
});

it('reports partial snippet var hints once with only the missing globals', function (): void {
    withIdeToolsStatusProject([], [
        'card.php' => <<<'PHP'
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Page $page
 */
echo $kirby->url();
echo $site->title();
echo $page->id();
PHP,
    ], function (): void {
        $status = (new IdeTools())->ideHelpersStatus(withDetails: true, limit: 5);

        expect($status['snippets']['withKirbyVarHints'])->toBe(0)
            ->and($status['snippets']['missingKirbyVarHints'])->toBe(1)
            ->and($status['snippets']['missing'])->toHaveCount(1)
            ->and($status['snippets']['missing'][0])->toMatchArray([
                'id' => 'card',
                'relativePath' => 'site/snippets/card.php',
                'missingVars' => ['$site'],
            ]);
    });
});
