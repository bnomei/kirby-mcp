<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\McpMarkedJsonExtractor;
use Symfony\Component\Process\Process;

it('supports vendor/bin-style install command', function (): void {
    $bin = realpath(__DIR__ . '/../../bin/kirby-mcp');
    expect($bin)->not()->toBeFalse();

    $projectRoot = cmsPath();

    $configDir = $projectRoot . DIRECTORY_SEPARATOR . '.kirby-mcp';
    $fixtureConfig = $configDir . DIRECTORY_SEPARATOR . 'config.json';
    $fixtureBackup = null;

    // Preserve fixture config.json if present
    if (is_file($fixtureConfig)) {
        $fixtureBackup = file_get_contents($fixtureConfig);
    }

    if (is_dir($configDir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($configDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
                continue;
            }

            @unlink($file->getPathname());
        }

        @rmdir($configDir);
    }

    try {
        $process = new Process(
            command: [PHP_BINARY, $bin, 'install', '--project=' . $projectRoot, '--json'],
            cwd: dirname(__DIR__, 2),
            timeout: 60,
        );

        $process->run();

        expect($process->getExitCode())->toBe(0);

        $decoded = McpMarkedJsonExtractor::extract($process->getOutput());
        expect($decoded)->toBeArray();
        expect($decoded)->toHaveKey('ok', true);
        expect($decoded)->toHaveKey('command', 'install');
        expect($decoded)->toHaveKey('projectRoot', $projectRoot);
        expect($decoded)->toHaveKey('config');
        expect($decoded['config'])->toHaveKey('created', true);
    } finally {
        // Restore fixture config.json
        if (is_string($fixtureBackup)) {
            if (!is_dir($configDir)) {
                mkdir($configDir, 0777, true);
            }
            file_put_contents($fixtureConfig, $fixtureBackup);
        }
    }
});

it('rejects global mode when combined with a project flag', function (): void {
    $bin = realpath(__DIR__ . '/../../bin/kirby-mcp');
    expect($bin)->not()->toBeFalse();

    $process = new Process(
        command: [PHP_BINARY, $bin, '--global', '--project=' . cmsPath()],
        cwd: dirname(__DIR__, 2),
        timeout: 15,
    );

    $process->run();

    expect($process->getExitCode())->toBe(1);
    expect($process->getErrorOutput())->toContain('--global is projectless');
});

it('rejects global mode when combined with project subcommands', function (): void {
    $bin = realpath(__DIR__ . '/../../bin/kirby-mcp');
    expect($bin)->not()->toBeFalse();

    $process = new Process(
        command: [PHP_BINARY, $bin, 'install', '--global'],
        cwd: dirname(__DIR__, 2),
        timeout: 15,
    );

    $process->run();

    expect($process->getExitCode())->toBe(1);
    expect($process->getErrorOutput())->toContain('--global starts the projectless reference MCP server');
});

it('supports vendor/bin-style update command', function (): void {
    $bin = realpath(__DIR__ . '/../../bin/kirby-mcp');
    expect($bin)->not()->toBeFalse();

    $projectRoot = cmsPath();

    $process = new Process(
        command: [PHP_BINARY, $bin, 'update', '--project=' . $projectRoot, '--json'],
        cwd: dirname(__DIR__, 2),
        timeout: 60,
    );

    $process->run();

    expect($process->getExitCode())->toBe(0);

    $decoded = McpMarkedJsonExtractor::extract($process->getOutput());
    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('ok', true);
    expect($decoded)->toHaveKey('command', 'update');
    expect($decoded)->toHaveKey('projectRoot', $projectRoot);
});

it('supports vendor/bin-style ide:status command', function (): void {
    $bin = realpath(__DIR__ . '/../../bin/kirby-mcp');
    expect($bin)->not()->toBeFalse();

    $projectRoot = cmsPath();

    $process = new Process(
        command: [PHP_BINARY, $bin, 'ide:status', '--project=' . $projectRoot, '--json'],
        cwd: dirname(__DIR__, 2),
        timeout: 60,
    );

    $process->run();

    expect($process->getExitCode())->toBe(0);

    $decoded = McpMarkedJsonExtractor::extract($process->getOutput());
    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('projectRoot', $projectRoot);
    expect($decoded)->toHaveKey('helpers');
    expect($decoded)->toHaveKey('templates');
    expect($decoded)->toHaveKey('snippets');
    expect($decoded)->toHaveKey('recommendations');
});

it('supports vendor/bin-style ide:generate command', function (): void {
    $bin = realpath(__DIR__ . '/../../bin/kirby-mcp');
    expect($bin)->not()->toBeFalse();

    $projectRoot = cmsPath();

    $process = new Process(
        command: [PHP_BINARY, $bin, 'ide:generate', '--project=' . $projectRoot, '--dry-run', '--prefer-filesystem', '--json'],
        cwd: dirname(__DIR__, 2),
        timeout: 120,
    );

    $process->run();

    expect($process->getExitCode())->toBe(0);

    $decoded = McpMarkedJsonExtractor::extract($process->getOutput());
    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('ok', true);
    expect($decoded)->toHaveKey('dryRun', true);
    expect($decoded)->toHaveKey('projectRoot', $projectRoot);
    expect($decoded)->toHaveKey('files');
    expect($decoded)->toHaveKey('stats');
});

it('exposes an explicit HTTP config check without changing normal bin commands', function (): void {
    $bin = realpath(__DIR__ . '/../../bin/kirby-mcp');
    expect($bin)->not()->toBeFalse();

    $projectRoot = cmsPath();

    $process = new Process(
        command: [PHP_BINARY, $bin, 'http', '--project=' . $projectRoot, '--check', '--json'],
        cwd: dirname(__DIR__, 2),
        timeout: 60,
    );

    $process->run();

    expect($process->getExitCode())->toBe(0);

    $decoded = McpMarkedJsonExtractor::extract($process->getOutput());
    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('ok', true);
    expect($decoded)->toHaveKey('command', 'http');
    expect($decoded['http'])->toHaveKey('enabled', false);
    expect($decoded['http'])->toHaveKey('host', '127.0.0.1');
    expect($decoded['http'])->toHaveKey('path', '/mcp');
});

it('reports enabled HTTP config without starting the listener when JSON output is requested', function (): void {
    $bin = realpath(__DIR__ . '/../../bin/kirby-mcp');
    expect($bin)->not()->toBeFalse();

    $projectRoot = cmsPath();

    $process = new Process(
        command: [PHP_BINARY, $bin, 'http', '--project=' . $projectRoot, '--json'],
        cwd: dirname(__DIR__, 2),
        env: [
            'KIRBY_MCP_HTTP_ENABLED' => '1',
            'KIRBY_MCP_HTTP_AUTH_MODE' => 'shared-token',
            'KIRBY_MCP_HTTP_TOKEN' => 'local-secret',
        ],
        timeout: 60,
    );

    $process->run();

    expect($process->getExitCode())->toBe(0);

    $decoded = McpMarkedJsonExtractor::extract($process->getOutput());
    expect($decoded)->toBeArray();
    expect($decoded)->toHaveKey('ok', true);
    expect($decoded['http'])->toHaveKey('enabled', true);
    expect($decoded['http'])->toHaveKey('authMode', 'shared-token');
    expect($decoded['errors'])->toBe([]);
});

it('fails closed before starting the HTTP listener when non-shared-token auth is configured', function (): void {
    $bin = realpath(__DIR__ . '/../../bin/kirby-mcp');
    expect($bin)->not()->toBeFalse();

    $projectRoot = cmsPath();

    $process = new Process(
        command: [PHP_BINARY, $bin, 'http', '--project=' . $projectRoot],
        cwd: dirname(__DIR__, 2),
        env: [
            'KIRBY_MCP_HTTP_ENABLED' => '1',
            'KIRBY_MCP_HTTP_AUTH_MODE' => 'oauth',
            'KIRBY_MCP_HTTP_OAUTH_ISSUER' => 'https://auth.example.test',
            'KIRBY_MCP_HTTP_OAUTH_AUDIENCE' => 'https://example.test/mcp',
            'KIRBY_MCP_HTTP_OAUTH_JWKS_URI' => 'https://auth.example.test/.well-known/jwks.json',
        ],
        timeout: 60,
    );

    $process->run();

    expect($process->getExitCode())->toBe(1);
    expect($process->getOutput())->toContain('HTTP listener auth currently supports shared-token only');
});
