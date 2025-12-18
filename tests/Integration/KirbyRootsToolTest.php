<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Tools\RootsTools;

it('exposes kirby roots via an MCP tool', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $data = new RootsTools()->roots();

    expect($data)->toHaveKey('roots');
    expect($data['roots'])->toHaveKey('index');
    expect($data['roots'])->toHaveKey('site');
    expect($data['roots'])->toHaveKey('content');
    expect($data['roots'])->toHaveKey('blueprints');
    expect($data['commandsRoot'])->toBeString();
});
