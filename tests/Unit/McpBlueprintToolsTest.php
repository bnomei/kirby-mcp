<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Tools\BlueprintTools;

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
