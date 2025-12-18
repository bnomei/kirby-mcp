<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Tools\CodeIndexTools;

it('supports idsOnly for templates/snippets/controllers/models/plugins index tools', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new CodeIndexTools();

    $templates = $tools->templatesIndex(idsOnly: true);
    expect($templates)->toHaveKey('templateIds');
    expect($templates['templateIds'])->toContain('home');
    expect($templates)->not()->toHaveKey('templates');

    $snippets = $tools->snippetsIndex(idsOnly: true);
    expect($snippets)->toHaveKey('snippetIds');
    expect($snippets['snippetIds'])->toContain('header');
    expect($snippets)->not()->toHaveKey('snippets');

    $controllers = $tools->controllersIndex(idsOnly: true);
    expect($controllers)->toHaveKey('controllerIds');
    expect($controllers['controllerIds'])->toContain('album');
    expect($controllers)->not()->toHaveKey('controllers');

    $models = $tools->modelsIndex(idsOnly: true);
    expect($models)->toHaveKey('modelIds');
    expect($models['modelIds'])->toContain('album');
    expect($models)->not()->toHaveKey('models');

    $plugins = $tools->pluginsIndex(idsOnly: true);
    expect($plugins)->toHaveKey('pluginIds');
    expect($plugins['pluginIds'])->toBeArray();
    expect($plugins)->not()->toHaveKey('plugins');
});

it('supports fields selection and pagination for templates index tool', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new CodeIndexTools();

    $index = $tools->templatesIndex(fields: ['relativePath'], limit: 1);
    expect($index)->toHaveKey('templates');
    expect(count($index['templates']))->toBe(1);

    $first = array_values($index['templates'])[0] ?? null;
    expect($first)->toBeArray();
    expect($first)->toHaveKey('id');
    expect($first)->toHaveKey('relativePath');
    expect($first)->not()->toHaveKey('name');
});
