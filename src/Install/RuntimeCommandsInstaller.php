<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Install;

use Bnomei\KirbyMcp\Project\KirbyRootsInspector;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class RuntimeCommandsInstaller
{
    private const SOURCE_DIR = 'commands';

    public function install(string $projectRoot, bool $force = false, ?string $commandsRootOverride = null): RuntimeCommandsInstallResult
    {
        $commandsRoot = null;

        if (is_string($commandsRootOverride) && $commandsRootOverride !== '') {
            $commandsRoot = $commandsRootOverride;
        } else {
            $roots = (new KirbyRootsInspector())->inspect($projectRoot);
            $commandsRoot = $roots->commandsRoot()
                ?? rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'commands';
        }

        $sourceRoot = $this->packageRoot() . DIRECTORY_SEPARATOR . self::SOURCE_DIR;
        if (!is_dir($sourceRoot)) {
            throw new \RuntimeException("Runtime command templates not found: {$sourceRoot}");
        }

        $installed = [];
        $skipped = [];
        $errors = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceRoot));

        foreach ($iterator as $file) {
            if ($file->isFile() === false || $file->getExtension() !== 'php') {
                continue;
            }

            $sourcePath = $file->getPathname();
            $relativePath = ltrim(substr($sourcePath, strlen($sourceRoot)), DIRECTORY_SEPARATOR);
            $destinationPath = rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;

            if (is_file($destinationPath) && $force === false) {
                $skipped[] = $relativePath;
                continue;
            }

            $destinationDir = dirname($destinationPath);
            if (!is_dir($destinationDir) && !mkdir($destinationDir, 0777, true) && !is_dir($destinationDir)) {
                $errors[] = [
                    'path' => $destinationDir,
                    'error' => 'Failed to create directory',
                ];
                continue;
            }

            $contents = file_get_contents($sourcePath);
            if ($contents === false) {
                $errors[] = [
                    'path' => $sourcePath,
                    'error' => 'Failed to read template file',
                ];
                continue;
            }

            $written = file_put_contents($destinationPath, $contents);
            if ($written === false) {
                $errors[] = [
                    'path' => $destinationPath,
                    'error' => 'Failed to write file',
                ];
                continue;
            }

            $installed[] = $relativePath;
        }

        sort($installed);
        sort($skipped);

        return new RuntimeCommandsInstallResult(
            projectRoot: $projectRoot,
            commandsRoot: $commandsRoot,
            installed: $installed,
            skipped: $skipped,
            errors: $errors,
        );
    }

    private function packageRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
