<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Tools\IdeTools;

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
