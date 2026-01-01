<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Resources\ConfigResources;
use Bnomei\KirbyMcp\Mcp\Tools\RuntimeTools;

it('reads config options via kirby://config/{option}', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $previousHost = getenv('KIRBY_MCP_HOST');
    putenv('KIRBY_MCP_HOST');

    $tools = new RuntimeTools();
    $install = $tools->runtimeInstall(force: true);
    $commandsRoot = $install['commandsRoot'];

    try {
        $resources = new ConfigResources();

        expect($resources->configGet('vendorname.pluginname.someoption'))
            ->toBe('vendorname.pluginname.someoption = 5');

        expect($resources->configGet('vendorname.pluginname.arrayoption'))
            ->toBe('vendorname.pluginname.arrayoption = {"a":1,"b":{"c":2}}');

        expect($resources->configGet('vendorname.pluginname.closureoption'))
            ->toBe('vendorname.pluginname.closureoption = Closure type');

        expect($resources->configGet('["vendorname","pluginname","someoption"]'))
            ->toBe('vendorname.pluginname.someoption = 5');

        putenv('KIRBY_MCP_HOST=example.test');
        expect($resources->configGet('vendorname.pluginname.someoption'))
            ->toBe('[example.test] vendorname.pluginname.someoption = 5');
    } finally {
        if ($previousHost === false) {
            putenv('KIRBY_MCP_HOST');
        } else {
            putenv('KIRBY_MCP_HOST=' . $previousHost);
        }

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
