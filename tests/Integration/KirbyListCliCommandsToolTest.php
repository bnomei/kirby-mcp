<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Resources\CliResources;
use Mcp\Exception\ResourceReadException;

it('lists Kirby CLI commands via kirby://commands', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $data = (new CliResources())->commands();

    expect($data['cli']['exitCode'])->toBe(0);
    expect($data['commands'])->toContain('version');
    expect($data['commands'])->toContain('roots');
    expect($data['sections'])->toHaveKey('core');
});

it('reads help for a specific CLI command via kirby://cli/command/{command}', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $data = (new CliResources())->command('version');

    expect($data['ok'])->toBeTrue();
    expect($data['command'])->toBe('version');
    expect($data['usage'])->toBeString()->not()->toBe('');
    expect($data['args'])->toHaveKey('required');
    expect($data['args'])->toHaveKey('optional');
    expect($data['cli']['exitCode'])->toBe(0);
});

it('rejects invalid CLI command identifiers', function (): void {
    $resource = new CliResources();

    expect(fn () => $resource->command('   '))
        ->toThrow(ResourceReadException::class, 'Command must not be empty.');
    expect(fn () => $resource->command('bad command'))
        ->toThrow(ResourceReadException::class, 'Command must not contain whitespace.');
});
