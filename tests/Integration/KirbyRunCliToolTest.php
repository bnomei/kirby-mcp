<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Tools\CliTools;
use Bnomei\KirbyMcp\Mcp\Tools\RuntimeTools;

it('runs a Kirby CLI command and returns raw output via kirby_run_cli_command', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $result = (new CliTools())->runCliCommand(command: 'version');

    expect($result['ok'])->toBeTrue();
    expect($result['exitCode'])->toBe(0);
    expect($result['stdout'])->toMatch('/\\b\\d+\\.\\d+\\.\\d+\\b/');
});

it('runs an allowlisted read-only Kirby CLI command', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $result = (new CliTools())->runCliCommand(command: 'version');

    expect($result['ok'])->toBeTrue();
    expect($result['policy']['matchedAllow'])->toBe('version');
    expect($result['exitCode'])->toBe(0);
    expect($result['stdout'])->toMatch('/\\b\\d+\\.\\d+\\.\\d+\\b/');
});

it('blocks write-capable commands unless allowWrite=true', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $result = (new CliTools())->runCliCommand(command: 'make:template', arguments: ['mcp_test_template']);

    expect($result['ok'])->toBeFalse();
    expect($result['message'])->toContain('allowWrite=true');
    expect($result['exitCode'])->toBeNull();
});

it('does not allow mcp:* write wrappers via kirby_run_cli_command by default', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $result = (new CliTools())->runCliCommand(command: 'mcp:page:update', allowWrite: true);

    expect($result['ok'])->toBeFalse();
    expect($result['policy']['matchedAllow'])->toBeNull();
    expect($result['policy']['matchedAllowWrite'])->toBeNull();
    expect($result['message'])->toContain('Command not allowed by default');
    expect($result['exitCode'])->toBeNull();
});

it('extracts marked JSON when running an mcp:* command', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $runtime = new RuntimeTools();
    $cli = new CliTools();

    $install = $runtime->runtimeInstall(force: true);
    $commandsRoot = $install['commandsRoot'];

    try {
        $result = $cli->runCliCommand(command: 'mcp:render', arguments: ['--type=html', '--max=2000']);

        expect($result['ok'])->toBeTrue();
        expect($result['mcpJson'])->toBeArray();
        expect($result['mcpJson'])->toHaveKey('ok');
        expect($result['mcpJson'])->toHaveKey('html');
    } finally {
        foreach ($install['installed'] as $relativePath) {
            $path = rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;
            if (is_file($path)) {
                @unlink($path);
            }
        }

        foreach ([
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'cli',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'page',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'config',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'site',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'file',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'user',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR),
        ] as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $entries = scandir($dir);
            if ($entries === false) {
                continue;
            }

            $remaining = array_diff($entries, ['.', '..']);
            if ($remaining === []) {
                rmdir($dir);
            }
        }
    }
});

it('respects deny patterns from .kirby-mcp/mcp.json', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    $projectRoot = cmsPath();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . $projectRoot);

    $configDir = $projectRoot . '/.kirby-mcp';
    $configFile = $configDir . '/mcp.json';

    if (is_file($configFile)) {
        @unlink($configFile);
    }

    if (!is_dir($configDir)) {
        mkdir($configDir, 0777, true);
    }

    file_put_contents($configFile, json_encode(['cli' => ['deny' => ['version']]], JSON_THROW_ON_ERROR));

    try {
        $result = (new CliTools())->runCliCommand(command: 'version');

        expect($result['ok'])->toBeFalse();
        expect($result['policy']['matchedDeny'])->toBe('version');
    } finally {
        if (is_file($configFile)) {
            @unlink($configFile);
        }

        if (is_dir($configDir)) {
            $entries = scandir($configDir);
            if (is_array($entries)) {
                $remaining = array_diff($entries, ['.', '..']);
                if ($remaining === []) {
                    rmdir($configDir);
                }
            }
        }
    }
});
