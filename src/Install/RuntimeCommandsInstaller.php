<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Install;

use Bnomei\KirbyMcp\Project\KirbyRootsInspector;
use Composer\InstalledVersions;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

final class RuntimeCommandsInstaller
{
    private const SOURCE_DIR = 'commands';

    private const PLUGIN_DIR = 'kirby-mcp';

    public function install(
        string $projectRoot,
        bool $force = false,
        ?string $commandsRootOverride = null,
        ?string $pluginsRootOverride = null,
    ): RuntimeCommandsInstallResult {
        $commandsRoot = null;

        $roots = null;
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

        if (is_string($pluginsRootOverride) && $pluginsRootOverride !== '') {
            $pluginsRoot = $pluginsRootOverride;
        } else {
            $roots ??= (new KirbyRootsInspector())->inspect($projectRoot);
            $pluginsRoot = $roots->get('plugins')
                ?? rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'plugins';
        }
        $plugin = $this->installPluginAdapter($pluginsRoot, $force);
        $errors = [...$errors, ...$plugin->errors];

        return new RuntimeCommandsInstallResult(
            projectRoot: $projectRoot,
            commandsRoot: $commandsRoot,
            installed: $installed,
            skipped: $skipped,
            errors: $errors,
            plugin: $plugin,
        );
    }

    private function installPluginAdapter(string $pluginsRoot, bool $force): PluginAdapterInstallResult
    {
        $root = rtrim($pluginsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . self::PLUGIN_DIR;
        $files = [
            'index.php' => $this->readPluginTemplate('index.php'),
            'index.js' => $this->readPluginTemplate('index.js'),
            'index.css' => $this->readPluginTemplate('index.css'),
            'composer.json' => $this->pluginManifest(),
        ];
        $installed = [];
        $skipped = [];
        $errors = [];

        foreach ($files as $name => $contents) {
            $path = $root . DIRECTORY_SEPARATOR . $name;
            if (is_file($path) && $force === false) {
                $skipped[] = $name;
                continue;
            }

            $blockedPath = $this->findBlockedPath($root);
            if ($blockedPath !== null) {
                $errors[] = ['path' => $blockedPath, 'error' => 'Plugin destination directory path is blocked by a file'];
                continue;
            }

            if (!is_dir($root) && !@mkdir($root, 0777, true) && !is_dir($root)) {
                $errors[] = ['path' => $root, 'error' => 'Failed to create plugin directory'];
                continue;
            }

            if ($contents === null) {
                $errors[] = ['path' => $path, 'error' => 'Failed to generate plugin adapter file'];
                continue;
            }

            if ($this->writeFileAtomically($path, $contents, 0644, $errors)) {
                $installed[] = $name;
            }
        }

        sort($installed);
        sort($skipped);

        return new PluginAdapterInstallResult($root, $installed, $skipped, $errors);
    }

    private function readPluginTemplate(string $name): ?string
    {
        $contents = file_get_contents($this->packageRoot() . DIRECTORY_SEPARATOR . 'plugin' . DIRECTORY_SEPARATOR . $name);

        return is_string($contents) ? $contents : null;
    }

    private function pluginManifest(): ?string
    {
        $json = json_encode([
            'name' => 'bnomei/kirby-mcp',
            'title' => 'Kirby MCP',
            'version' => $this->packageVersion(),
            'type' => 'kirby-plugin',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return is_string($json) ? $json . "\n" : null;
    }

    private function packageVersion(): string
    {
        try {
            if (InstalledVersions::isInstalled('bnomei/kirby-mcp')) {
                $version = InstalledVersions::getPrettyVersion('bnomei/kirby-mcp')
                    ?? InstalledVersions::getVersion('bnomei/kirby-mcp');
                if (is_string($version) && $version !== '') {
                    return $version;
                }
            }
        } catch (Throwable) {
            // Fall back to package metadata when Composer runtime data is unavailable.
        }

        $contents = @file_get_contents($this->packageRoot() . DIRECTORY_SEPARATOR . 'composer.json');
        $metadata = is_string($contents) ? json_decode($contents, true) : null;
        $version = is_array($metadata) ? ($metadata['version'] ?? null) : null;

        return is_string($version) && $version !== '' ? $version : '0.0.0';
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
