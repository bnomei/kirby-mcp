<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Commands\EvalPhp;
use Bnomei\KirbyMcp\Mcp\Tools\RuntimeTools;

it('returns a dry-run response for eval when confirm=false', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());
    putenv(EvalPhp::ENV_ENABLE_EVAL . '=1');

    $tools = new RuntimeTools();

    $install = $tools->runtimeInstall(force: true);
    $commandsRoot = $install['commandsRoot'];

    try {
        $result = $tools->evalPhp(
            code: 'echo "SHOULD_NOT_RUN"; return 123;',
            confirm: false,
        );

        expect($result)->toHaveKey('ok', false);
        expect($result['needsConfirm'] ?? null)->toBeTrue();
        expect($result['stdout'] ?? '')->toBe('');
    } finally {
        putenv(EvalPhp::ENV_ENABLE_EVAL . '=');

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

it('executes eval code when enabled and confirm=true', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());
    putenv(EvalPhp::ENV_ENABLE_EVAL . '=1');

    $tools = new RuntimeTools();

    $install = $tools->runtimeInstall(force: true);
    $commandsRoot = $install['commandsRoot'];

    try {
        $result = $tools->evalPhp(
            code: 'echo "HELLO"; return $site->homePage()->title()->value();',
            confirm: true,
        );

        expect($result)->toHaveKey('ok', true);
        expect($result['stdout'] ?? null)->toContain('HELLO');

        $return = $result['return'] ?? null;
        expect($return)->toBeArray();
        expect($return['json'] ?? null)->toBe('Home');
    } finally {
        putenv(EvalPhp::ENV_ENABLE_EVAL . '=');

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
