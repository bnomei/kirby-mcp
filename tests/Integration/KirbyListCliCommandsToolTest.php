<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Tools\CliTools;

it('lists Kirby CLI commands via MCP tool', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $data = (new CliTools())->listCliCommands();

    expect($data['cli']['exitCode'])->toBe(0);
    expect($data['commands'])->toContain('version');
    expect($data['commands'])->toContain('roots');
    expect($data['sections'])->toHaveKey('core');
});
