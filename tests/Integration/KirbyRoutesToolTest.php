<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Tools\RoutesTools;
use Bnomei\KirbyMcp\Mcp\Tools\RuntimeTools;

it('indexes routes and reports their defining source (config vs plugin)', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $runtime = new RuntimeTools();
    $install = $runtime->runtimeInstall(force: true);
    $commandsRoot = $install['commandsRoot'];

    try {
        $tools = new RoutesTools();
        $index = $tools->routesIndex(patternContains: 'mcp-test/', limit: 0, cursor: 0);

        expect($index)->toHaveKey('ok', true);
        expect($index)->toHaveKey('mode', 'runtime');
        expect($index)->toHaveKey('routes');
        expect($index['routes'])->toBeArray()->not()->toBeEmpty();

        $patterns = array_map(
            static fn (array $route): string => (string) ($route['pattern'] ?? ''),
            $index['routes'],
        );

        expect($patterns)->toContain('mcp-test/config-route');
        expect($patterns)->toContain('mcp-test/plugin-route');

        $byPattern = [];
        foreach ($index['routes'] as $route) {
            $pattern = $route['pattern'] ?? null;
            if (!is_string($pattern) || $pattern === '') {
                continue;
            }
            $byPattern[$pattern] = $route;
        }

        $configRoute = $byPattern['mcp-test/config-route'] ?? null;
        expect($configRoute)->toBeArray();
        expect($configRoute)->toHaveKey('source.kind', 'config');
        expect($configRoute)->toHaveKey('source.relativePath');
        expect((string) ($configRoute['source']['relativePath'] ?? ''))->toContain(
            'site' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php',
        );

        $pluginRoute = $byPattern['mcp-test/plugin-route'] ?? null;
        expect($pluginRoute)->toBeArray();
        expect($pluginRoute)->toHaveKey('source.kind', 'plugin');
        expect($pluginRoute)->toHaveKey('source.pluginId', 'mcp/test-routes');
        expect($pluginRoute)->toHaveKey('source.relativePath');
        expect((string) ($pluginRoute['source']['relativePath'] ?? ''))->toContain(
            'site' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'mcp-test' . DIRECTORY_SEPARATOR . 'index.php',
        );
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
