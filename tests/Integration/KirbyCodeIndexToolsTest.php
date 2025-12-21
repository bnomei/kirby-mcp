<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Tools\CodeIndexTools;

/**
 * @param callable(): void $callback
 */
function withTemplatesCommandRemoved(callable $callback): void
{
    $commandFile = cmsPath() . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'templates.php';
    $backup = null;

    if (is_file($commandFile)) {
        $backup = file_get_contents($commandFile);
        @unlink($commandFile);
    }

    try {
        $callback();
    } finally {
        if (is_string($backup)) {
            $dir = dirname($commandFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($commandFile, $backup);
        }
    }
}

it('indexes templates via Kirby roots', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new CodeIndexTools();

    $index = $tools->templatesIndex();

    expect($index['exists'])->toBeTrue();
    expect($index['templates'])->toHaveKey('home');
    expect($index['templates']['home']['relativePath'])->toBe('site/templates/home.php');
});

it('filters templates index by activeSource=file in filesystem mode', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    withTemplatesCommandRemoved(function (): void {
        $tools = new CodeIndexTools();

        $index = $tools->templatesIndex(idsOnly: true, activeSource: 'file');

        expect($index['mode'])->toBe('filesystem');
        expect($index['filters'])->toHaveKey('activeSource', 'file');
        expect($index)->toHaveKey('templateIds');
        expect($index['templateIds'])->toContain('home');
        expect($index['counts'])->toHaveKey('filtered', $index['counts']['total']);
    });
});

it('filters templates index by activeSource=extension in filesystem mode', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    withTemplatesCommandRemoved(function (): void {
        $tools = new CodeIndexTools();

        $index = $tools->templatesIndex(idsOnly: true, activeSource: 'extension');

        expect($index['mode'])->toBe('filesystem');
        expect($index['filters'])->toHaveKey('activeSource', 'extension');
        expect($index)->toHaveKey('templateIds');
        expect($index['templateIds'])->toBe([]);
        expect($index['counts'])->toHaveKey('filtered', 0);
    });
});

it('filters templates index by overriddenOnly in filesystem mode', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    withTemplatesCommandRemoved(function (): void {
        $tools = new CodeIndexTools();

        $index = $tools->templatesIndex(idsOnly: true, overriddenOnly: true);

        expect($index['mode'])->toBe('filesystem');
        expect($index['filters'])->toHaveKey('overriddenOnly', true);
        expect($index)->toHaveKey('templateIds');
        expect($index['templateIds'])->toBe([]);
        expect($index['counts'])->toHaveKey('filtered', 0);
    });
});

it('ignores invalid activeSource filters in filesystem mode', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    withTemplatesCommandRemoved(function (): void {
        $tools = new CodeIndexTools();

        $index = $tools->templatesIndex(idsOnly: true, activeSource: 'invalid');

        expect($index['mode'])->toBe('filesystem');
        expect($index['filters'])->toBe([]);
        expect($index)->toHaveKey('templateIds');
        expect($index['templateIds'])->toContain('home');
    });
});

it('indexes snippets via Kirby roots', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new CodeIndexTools();

    $index = $tools->snippetsIndex();

    expect($index['exists'])->toBeTrue();
    expect($index['snippets'])->toHaveKey('header');
    expect($index['snippets'])->toHaveKey('blocks/gallery');
});

it('indexes collections via Kirby roots', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new CodeIndexTools();

    $index = $tools->collectionsIndex();

    expect($index['exists'])->toBeTrue();
    expect($index['collections'])->toHaveKey('notes');
    expect($index['collections']['notes']['relativePath'])->toBe('site/collections/notes.php');
});

it('indexes controllers via Kirby roots', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new CodeIndexTools();

    $index = $tools->controllersIndex();

    expect($index['exists'])->toBeTrue();
    expect($index['controllers'])->toHaveKey('album');
});

it('indexes models via Kirby roots', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new CodeIndexTools();

    $index = $tools->modelsIndex();

    expect($index['exists'])->toBeTrue();
    expect($index['models'])->toHaveKey('album');
});

it('indexes plugins via Kirby roots (empty in cms fixture)', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new CodeIndexTools();

    $index = $tools->pluginsIndex();

    expect($index)->toHaveKey('pluginsRoot');
    expect($index['plugins'])->toBeArray();
});
