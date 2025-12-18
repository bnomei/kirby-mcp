<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Tools\CodeIndexTools;

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
