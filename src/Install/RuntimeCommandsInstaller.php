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
            $blockedPath = $this->findBlockedPath($destinationDir);
            if (is_string($blockedPath) && $blockedPath !== '') {
                $errors[] = [
                    'path' => $blockedPath,
                    'error' => 'Destination directory path is blocked by a file',
                ];
                continue;
            }

            if (!is_dir($destinationDir) && !@mkdir($destinationDir, 0777, true) && !is_dir($destinationDir)) {
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

            $sourceMode = @fileperms($sourcePath);
            $sourceMode = is_int($sourceMode) ? ($sourceMode & 0777) : null;

            if ($this->writeFileAtomically($destinationPath, $contents, $sourceMode, $errors) === false) {
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

    private function findBlockedPath(string $path): ?string
    {
        $current = rtrim($path, DIRECTORY_SEPARATOR);
        while ($current !== '' && $current !== dirname($current)) {
            if (is_file($current)) {
                return $current;
            }

            if (is_dir($current)) {
                return null;
            }

            $current = dirname($current);
        }

        return null;
    }

    /**
     * @param array<int, array{path: string, error: string}> $errors
     */
    private function writeFileAtomically(string $destinationPath, string $contents, ?int $mode, array &$errors): bool
    {
        $destinationDir = dirname($destinationPath);
        $tempFile = tempnam($destinationDir, 'kirby-mcp-');

        if ($tempFile === false) {
            $errors[] = [
                'path' => $destinationPath,
                'error' => 'Failed to create temp file for atomic write',
            ];
            return false;
        }

        $written = file_put_contents($tempFile, $contents);
        if ($written === false) {
            @unlink($tempFile);
            $errors[] = [
                'path' => $destinationPath,
                'error' => 'Failed to write temp file for atomic write',
            ];
            return false;
        }

        if (is_int($mode)) {
            @chmod($tempFile, $mode);
        }

        if (@rename($tempFile, $destinationPath)) {
            return true;
        }

        if (is_file($destinationPath) && @unlink($destinationPath) && @rename($tempFile, $destinationPath)) {
            return true;
        }

        @unlink($tempFile);
        $errors[] = [
            'path' => $destinationPath,
            'error' => 'Failed to move temp file into place',
        ];

        return false;
    }
}
