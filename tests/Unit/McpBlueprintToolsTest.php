<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Tools\BlueprintTools;

/**
 * @param array<int, string> $relativePaths
 * @param callable(): void $callback
 */
function withBlueprintCommandFilesRemoved(array $relativePaths, callable $callback): void
{
    $commandsRoot = cmsPath() . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'commands' . DIRECTORY_SEPARATOR;
    $backups = [];

    foreach ($relativePaths as $relativePath) {
        $relativePath = ltrim($relativePath, '/');
        $path = $commandsRoot . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        if (!is_file($path)) {
            continue;
        }

        $backups[$path] = file_get_contents($path);
        @unlink($path);
    }

    try {
        $callback();
    } finally {
        foreach ($backups as $path => $contents) {
            if (!is_string($contents)) {
                continue;
            }

            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            file_put_contents($path, $contents);
        }
    }
}

it('exposes a static blueprints index tool', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $data = (new BlueprintTools())->blueprintsIndex();

    expect($data)->toHaveKey('blueprints');
    expect($data['blueprints'])->toHaveKey('pages/home');
    expect($data['blueprints'])->toHaveKey('site');
    expect($data)->not()->toHaveKey('cli');

    expect($data['blueprints']['pages/home']['displayName'])->toBe('Home');
    expect($data['blueprints']['pages/home']['displayNameSource'])->toBe('title');

    expect($data['blueprints'])->toHaveKey('sections/notes');
    expect($data['blueprints']['sections/notes']['displayName'])->toBe('Notes');
    expect($data['blueprints']['sections/notes']['displayNameSource'])->toBe('label');

    expect($data['blueprints'])->toHaveKey('fields/cover');
    expect($data['blueprints']['fields/cover']['displayName'])->toBe('cover');
    expect($data['blueprints']['fields/cover']['displayNameSource'])->toBe('id');
});

it('falls back to filesystem indexing when runtime blueprints command is missing', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    withBlueprintCommandFilesRemoved(['mcp/blueprints.php'], function (): void {
        $data = (new BlueprintTools())->blueprintsIndex(withData: true, type: 'page');

        expect($data)->toHaveKey('mode', 'filesystem');
        expect($data)->toHaveKey('needsRuntimeInstall', true);
        expect($data['filters'])->toHaveKey('type', 'page');
        expect($data)->toHaveKey('blueprints');
        expect($data['blueprints'])->toHaveKey('pages/home');
        expect($data['counts']['withData'])->toBeGreaterThan(0);
    });
});

it('filters blueprints index by activeSource=extension in filesystem mode', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    withBlueprintCommandFilesRemoved(['mcp/blueprints.php'], function (): void {
        $data = (new BlueprintTools())->blueprintsIndex(idsOnly: true, activeSource: 'extension');

        expect($data)->toHaveKey('mode', 'filesystem');
        expect($data['filters'])->toHaveKey('activeSource', 'extension');
        expect($data)->toHaveKey('blueprintIds');
        expect($data['blueprintIds'])->toBe([]);
        expect($data['counts']['filtered'])->toBe(0);
    });
});

it('filters blueprints index by overriddenOnly in filesystem mode', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    withBlueprintCommandFilesRemoved(['mcp/blueprints.php'], function (): void {
        $data = (new BlueprintTools())->blueprintsIndex(idsOnly: true, overriddenOnly: true);

        expect($data)->toHaveKey('mode', 'filesystem');
        expect($data['filters'])->toHaveKey('overriddenOnly', true);
        expect($data)->toHaveKey('blueprintIds');
        expect($data['blueprintIds'])->toBe([]);
        expect($data['counts']['filtered'])->toBe(0);
    });
});

it('supports idsOnly for the blueprints index tool', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $data = (new BlueprintTools())->blueprintsIndex(idsOnly: true);

    expect($data)->toHaveKey('blueprintIds');
    expect($data['blueprintIds'])->toContain('pages/home');
    expect($data)->not()->toHaveKey('blueprints');
});

it('supports fields selection for the blueprints index tool', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $data = (new BlueprintTools())->blueprintsIndex(fields: ['displayName']);

    expect($data)->toHaveKey('blueprints');
    expect($data['blueprints'])->toHaveKey('pages/home');

    $entry = $data['blueprints']['pages/home'];
    expect($entry)->toHaveKey('id', 'pages/home');
    expect($entry)->toHaveKey('displayName', 'Home');
    expect($entry)->not()->toHaveKey('type');
});

it('supports pagination for the blueprints index tool', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $data = (new BlueprintTools())->blueprintsIndex(limit: 1);

    expect($data)->toHaveKey('blueprints');
    expect($data['blueprints'])->toBeArray();
    expect(count($data['blueprints']))->toBe(1);
});

it('reads a single blueprint via tool', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $data = (new BlueprintTools())->blueprintRead(id: 'pages/home');

    expect($data)->toHaveKey('ok', true);
    expect($data)->toHaveKey('id', 'pages/home');
    expect($data)->toHaveKey('data');
    expect($data['data'])->toBeArray();
});

it('reads a blueprint from the filesystem when runtime command is missing', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    withBlueprintCommandFilesRemoved(['mcp/blueprint.php'], function (): void {
        $data = (new BlueprintTools())->blueprintRead(id: 'pages/home', withData: false);

        expect($data)->toHaveKey('ok', true);
        expect($data)->toHaveKey('mode', 'filesystem');
        expect($data)->toHaveKey('needsRuntimeInstall', true);
        expect($data)->toHaveKey('id', 'pages/home');
        expect($data)->not()->toHaveKey('data');
    });
});

it('returns filesystem error when blueprint is missing and runtime command is missing', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    withBlueprintCommandFilesRemoved(['mcp/blueprint.php'], function (): void {
        $data = (new BlueprintTools())->blueprintRead(id: 'pages/does-not-exist');

        expect($data)->toHaveKey('ok', false);
        expect($data)->toHaveKey('mode', 'filesystem');
        expect($data)->toHaveKey('needsRuntimeInstall', true);
        expect($data)->toHaveKey('error');
    });
});

it('can omit blueprint data payload via tool', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $data = (new BlueprintTools())->blueprintRead(id: 'pages/home', withData: false);

    expect($data)->toHaveKey('ok', true);
    expect($data)->toHaveKey('id', 'pages/home');
    expect($data)->not()->toHaveKey('data');
    expect($data)->not()->toHaveKey('cli');
});
