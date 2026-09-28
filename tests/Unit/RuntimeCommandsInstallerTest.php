<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Install\RuntimeCommandsInstaller;
use Composer\InstalledVersions;

function runtimeInstallerTempDir(string $suffix): string
{
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-install-' . $suffix . '-' . bin2hex(random_bytes(4));
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return $dir;
}

function removeRuntimeInstallerDir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }

    rmdir($dir);
}

function runtimeInstallerPluginsRoot(string $projectRoot): string
{
    return $projectRoot . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'plugins';
}

it('records install errors when a destination directory cannot be created', function (): void {
    $projectRoot = runtimeInstallerTempDir('project');
    $commandsRoot = $projectRoot . DIRECTORY_SEPARATOR . 'commands';

    mkdir($commandsRoot, 0777, true);
    file_put_contents($commandsRoot . DIRECTORY_SEPARATOR . 'mcp', 'blocked');

    try {
        $result = (new RuntimeCommandsInstaller())->install(
            projectRoot: $projectRoot,
            force: true,
            commandsRootOverride: $commandsRoot,
            pluginsRootOverride: runtimeInstallerPluginsRoot($projectRoot),
        );

        expect($result->installed)->toBeEmpty();
        expect($result->errors)->not()->toBeEmpty();
        expect($result->errors[0])->toHaveKey('path');
        expect($result->errors[0]['path'])->toContain($commandsRoot . DIRECTORY_SEPARATOR . 'mcp');

        expect($result->ok())->toBeFalse();
        expect($result->toArray()['ok'])->toBeFalse();
    } finally {
        removeRuntimeInstallerDir($projectRoot);
    }
});

it('reports ok=true and exposes the flag in toArray when every file installs', function (): void {
    $projectRoot = runtimeInstallerTempDir('project-ok');
    $commandsRoot = $projectRoot . DIRECTORY_SEPARATOR . 'commands';

    try {
        $result = (new RuntimeCommandsInstaller())->install(
            projectRoot: $projectRoot,
            force: true,
            commandsRootOverride: $commandsRoot,
            pluginsRootOverride: runtimeInstallerPluginsRoot($projectRoot),
        );

        expect($result->installed)->not()->toBeEmpty();
        expect($result->errors)->toBeEmpty();
        expect($result->ok())->toBeTrue();

        $array = $result->toArray();
        expect($array['ok'])->toBeTrue();
        expect(array_key_first($array))->toBe('ok');
    } finally {
        removeRuntimeInstallerDir($projectRoot);
    }
});

it('installs a physical Kirby plugin adapter with generated package metadata', function (): void {
    $projectRoot = runtimeInstallerTempDir('plugin-adapter');
    $commandsRoot = $projectRoot . DIRECTORY_SEPARATOR . 'commands';

    try {
        $result = (new RuntimeCommandsInstaller())->install(
            projectRoot: $projectRoot,
            force: false,
            commandsRootOverride: $commandsRoot,
            pluginsRootOverride: runtimeInstallerPluginsRoot($projectRoot),
        );

        $pluginRoot = $projectRoot . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'kirby-mcp';
        expect($result->plugin->root)->toBe($pluginRoot)
            ->and($result->plugin->installed)->toBe(['composer.json', 'index.php'])
            ->and($result->plugin->skipped)->toBeEmpty()
            ->and($result->plugin->errors)->toBeEmpty()
            ->and(is_link($pluginRoot . DIRECTORY_SEPARATOR . 'index.php'))->toBeFalse()
            ->and(file_get_contents($pluginRoot . DIRECTORY_SEPARATOR . 'index.php'))
            ->toContain('Bnomei\\KirbyMcp\\Mcp\\Plugin::register();');

        $metadata = json_decode((string)file_get_contents($pluginRoot . DIRECTORY_SEPARATOR . 'composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $installedVersion = InstalledVersions::getPrettyVersion('bnomei/kirby-mcp')
            ?? InstalledVersions::getVersion('bnomei/kirby-mcp')
            ?? '0.0.0';

        expect($metadata)->toBe([
            'name' => 'bnomei/kirby-mcp',
            'title' => 'Kirby MCP',
            'version' => $installedVersion,
            'type' => 'kirby-plugin',
        ])->and($result->toArray()['plugin'])->toBe($result->plugin->toArray());
    } finally {
        removeRuntimeInstallerDir($projectRoot);
    }
});

it('preserves existing plugin adapter files unless force is enabled', function (): void {
    $projectRoot = runtimeInstallerTempDir('plugin-force');
    $commandsRoot = $projectRoot . DIRECTORY_SEPARATOR . 'commands';

    try {
        $installer = new RuntimeCommandsInstaller();
        $pluginsRoot = runtimeInstallerPluginsRoot($projectRoot);
        $first = $installer->install($projectRoot, false, $commandsRoot, $pluginsRoot);
        $index = $first->plugin->root . DIRECTORY_SEPARATOR . 'index.php';
        file_put_contents($index, 'custom adapter');

        $skipped = $installer->install($projectRoot, false, $commandsRoot, $pluginsRoot);
        expect($skipped->plugin->installed)->toBeEmpty()
            ->and($skipped->plugin->skipped)->toBe(['composer.json', 'index.php'])
            ->and(file_get_contents($index))->toBe('custom adapter');

        $updated = $installer->install($projectRoot, true, $commandsRoot, $pluginsRoot);
        expect($updated->plugin->installed)->toBe(['composer.json', 'index.php'])
            ->and($updated->plugin->skipped)->toBeEmpty()
            ->and(file_get_contents($index))->toContain('Plugin::register();');
    } finally {
        removeRuntimeInstallerDir($projectRoot);
    }
});

it('reports plugin adapter destination errors in both plugin and aggregate outcomes', function (): void {
    $projectRoot = runtimeInstallerTempDir('plugin-error');
    $commandsRoot = $projectRoot . DIRECTORY_SEPARATOR . 'commands';
    $pluginsRoot = $projectRoot . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'plugins';

    mkdir($pluginsRoot, 0777, true);
    file_put_contents($pluginsRoot . DIRECTORY_SEPARATOR . 'kirby-mcp', 'blocked');

    try {
        $result = (new RuntimeCommandsInstaller())->install($projectRoot, true, $commandsRoot, $pluginsRoot);

        expect($result->plugin->installed)->toBeEmpty()
            ->and($result->plugin->errors)->toHaveCount(2)
            ->and($result->errors)->toContain(...$result->plugin->errors)
            ->and($result->ok())->toBeFalse();
    } finally {
        removeRuntimeInstallerDir($projectRoot);
    }
});
