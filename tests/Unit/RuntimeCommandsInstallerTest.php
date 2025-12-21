<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Install\RuntimeCommandsInstaller;

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
        );

        expect($result->installed)->toBeEmpty();
        expect($result->errors)->not()->toBeEmpty();
        expect($result->errors[0])->toHaveKey('path');
        expect($result->errors[0]['path'])->toContain($commandsRoot . DIRECTORY_SEPARATOR . 'mcp');
    } finally {
        removeRuntimeInstallerDir($projectRoot);
    }
});
