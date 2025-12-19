<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Project;

use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class RootsCodeIndexer
{
    /**
     * Filesystem-only indexer for Kirby project roots.
     *
     * Note: This intentionally does not try to infer plugin-registered templates/snippets/controllers
     * by crawling plugin folders. Prefer the runtime-backed MCP CLI commands (`mcp:*`) and the
     * corresponding tools (e.g. `kirby_templates_index`) when you need Kirby’s runtime truth.
     */
    private const COMMON_REPRESENTATIONS = [
        'html',
        'json',
        'xml',
        'rss',
        'txt',
        'atom',
        'csv',
    ];

    /**
     * @return array{
     *   projectRoot: string,
     *   templatesRoot: string,
     *   exists: bool,
     *   templates: array<string, array{
     *     id: string,
     *     name: string,
     *     representation: string|null,
     *     absolutePath: string,
     *     relativePath: string,
     *     rootRelativePath: string
     *   }>
     * }
     */
    public function templates(string $projectRoot, KirbyRoots $roots): array
    {
        $templatesRoot = $this->resolveRoot($projectRoot, $roots, 'templates', 'site/templates');
        $files = $this->scanPhpFiles($templatesRoot);

        $templates = [];
        foreach ($files as $rootRelativePath => $absolutePath) {
            $stem = $this->stripPhpExtension($rootRelativePath);
            $idStem = str_replace('/', '.', $stem);
            [$name, $representation] = $this->splitRepresentation($idStem);

            $id = $representation !== null ? $name . '.' . $representation : $name;

            $templates[$id] = [
                'id' => $id,
                'name' => $name,
                'representation' => $representation,
                'absolutePath' => $absolutePath,
                'relativePath' => $this->relativeToProject($projectRoot, $absolutePath),
                'rootRelativePath' => $rootRelativePath,
            ];
        }

        ksort($templates);

        return [
            'projectRoot' => $projectRoot,
            'templatesRoot' => $templatesRoot,
            'exists' => is_dir($templatesRoot),
            'templates' => $templates,
        ];
    }

    /**
     * @return array{
     *   projectRoot: string,
     *   snippetsRoot: string,
     *   exists: bool,
     *   snippets: array<string, array{
     *     id: string,
     *     name: string,
     *     absolutePath: string,
     *     relativePath: string,
     *     rootRelativePath: string
     *   }>
     * }
     */
    public function snippets(string $projectRoot, KirbyRoots $roots): array
    {
        $snippetsRoot = $this->resolveRoot($projectRoot, $roots, 'snippets', 'site/snippets');
        $files = $this->scanPhpFiles($snippetsRoot);

        $snippets = [];
        foreach ($files as $rootRelativePath => $absolutePath) {
            $id = $this->stripPhpExtension($rootRelativePath);

            $snippets[$id] = [
                'id' => $id,
                'name' => $id,
                'absolutePath' => $absolutePath,
                'relativePath' => $this->relativeToProject($projectRoot, $absolutePath),
                'rootRelativePath' => $rootRelativePath,
            ];
        }

        ksort($snippets);

        return [
            'projectRoot' => $projectRoot,
            'snippetsRoot' => $snippetsRoot,
            'exists' => is_dir($snippetsRoot),
            'snippets' => $snippets,
        ];
    }

    /**
     * @return array{
     *   projectRoot: string,
     *   collectionsRoot: string,
     *   exists: bool,
     *   collections: array<string, array{
     *     id: string,
     *     name: string,
     *     absolutePath: string,
     *     relativePath: string,
     *     rootRelativePath: string
     *   }>
     * }
     */
    public function collections(string $projectRoot, KirbyRoots $roots): array
    {
        $collectionsRoot = $this->resolveRoot($projectRoot, $roots, 'collections', 'site/collections');
        $files = $this->scanPhpFiles($collectionsRoot);

        $collections = [];
        foreach ($files as $rootRelativePath => $absolutePath) {
            $id = $this->stripPhpExtension($rootRelativePath);

            $collections[$id] = [
                'id' => $id,
                'name' => $id,
                'absolutePath' => $absolutePath,
                'relativePath' => $this->relativeToProject($projectRoot, $absolutePath),
                'rootRelativePath' => $rootRelativePath,
            ];
        }

        ksort($collections);

        return [
            'projectRoot' => $projectRoot,
            'collectionsRoot' => $collectionsRoot,
            'exists' => is_dir($collectionsRoot),
            'collections' => $collections,
        ];
    }

    /**
     * @return array{
     *   projectRoot: string,
     *   controllersRoot: string,
     *   exists: bool,
     *   controllers: array<string, array{
     *     id: string,
     *     name: string,
     *     representation: string|null,
     *     absolutePath: string,
     *     relativePath: string,
     *     rootRelativePath: string
     *   }>
     * }
     */
    public function controllers(string $projectRoot, KirbyRoots $roots): array
    {
        $controllersRoot = $this->resolveRoot($projectRoot, $roots, 'controllers', 'site/controllers');
        $files = $this->scanPhpFiles($controllersRoot);

        $controllers = [];
        foreach ($files as $rootRelativePath => $absolutePath) {
            $stem = $this->stripPhpExtension($rootRelativePath);
            $idStem = str_replace('/', '.', $stem);
            [$name, $representation] = $this->splitRepresentation($idStem);

            $id = $representation !== null ? $name . '.' . $representation : $name;

            $controllers[$id] = [
                'id' => $id,
                'name' => $name,
                'representation' => $representation,
                'absolutePath' => $absolutePath,
                'relativePath' => $this->relativeToProject($projectRoot, $absolutePath),
                'rootRelativePath' => $rootRelativePath,
            ];
        }

        ksort($controllers);

        return [
            'projectRoot' => $projectRoot,
            'controllersRoot' => $controllersRoot,
            'exists' => is_dir($controllersRoot),
            'controllers' => $controllers,
        ];
    }

    /**
     * @return array{
     *   projectRoot: string,
     *   modelsRoot: string,
     *   exists: bool,
     *   models: array<string, array{
     *     id: string,
     *     name: string,
     *     representation: string|null,
     *     absolutePath: string,
     *     relativePath: string,
     *     rootRelativePath: string
     *   }>
     * }
     */
    public function models(string $projectRoot, KirbyRoots $roots): array
    {
        $modelsRoot = $this->resolveRoot($projectRoot, $roots, 'models', 'site/models');
        $files = $this->scanPhpFiles($modelsRoot);

        $models = [];
        foreach ($files as $rootRelativePath => $absolutePath) {
            $stem = $this->stripPhpExtension($rootRelativePath);
            $idStem = str_replace('/', '.', $stem);
            [$name, $representation] = $this->splitRepresentation($idStem);

            $id = $representation !== null ? $name . '.' . $representation : $name;

            $models[$id] = [
                'id' => $id,
                'name' => $name,
                'representation' => $representation,
                'absolutePath' => $absolutePath,
                'relativePath' => $this->relativeToProject($projectRoot, $absolutePath),
                'rootRelativePath' => $rootRelativePath,
            ];
        }

        ksort($models);

        return [
            'projectRoot' => $projectRoot,
            'modelsRoot' => $modelsRoot,
            'exists' => is_dir($modelsRoot),
            'models' => $models,
        ];
    }

    /**
     * @return array{
     *   projectRoot: string,
     *   pluginsRoot: string,
     *   exists: bool,
     *   plugins: array<string, array{
     *     id: string,
     *     dirName: string,
     *     absolutePath: string,
     *     relativePath: string,
     *     hasIndexPhp: bool,
     *     hasComposerJson: bool,
     *     hasPackageJson: bool,
     *     hasBlueprints: bool,
     *     hasSnippets: bool,
     *     hasTemplates: bool,
     *     hasControllers: bool,
     *     hasModels: bool,
     *     hasCommands: bool
     *   }>
     * }
     */
    public function plugins(string $projectRoot, KirbyRoots $roots): array
    {
        $pluginsRoot = $this->resolveRoot($projectRoot, $roots, 'plugins', 'site/plugins');

        $plugins = [];

        if (is_dir($pluginsRoot)) {
            foreach (new DirectoryIterator($pluginsRoot) as $entry) {
                if ($entry->isDot()) {
                    continue;
                }

                if ($entry->isDir() === false) {
                    continue;
                }

                $dirName = $entry->getFilename();
                if ($dirName === '' || str_starts_with($dirName, '.')) {
                    continue;
                }

                $absolutePath = $entry->getPathname();
                $id = $dirName;

                $plugins[$id] = [
                    'id' => $id,
                    'dirName' => $dirName,
                    'absolutePath' => $absolutePath,
                    'relativePath' => $this->relativeToProject($projectRoot, $absolutePath),
                    'hasIndexPhp' => is_file($absolutePath . DIRECTORY_SEPARATOR . 'index.php'),
                    'hasComposerJson' => is_file($absolutePath . DIRECTORY_SEPARATOR . 'composer.json'),
                    'hasPackageJson' => is_file($absolutePath . DIRECTORY_SEPARATOR . 'package.json'),
                    'hasBlueprints' => is_dir($absolutePath . DIRECTORY_SEPARATOR . 'blueprints'),
                    'hasSnippets' => is_dir($absolutePath . DIRECTORY_SEPARATOR . 'snippets'),
                    'hasTemplates' => is_dir($absolutePath . DIRECTORY_SEPARATOR . 'templates'),
                    'hasControllers' => is_dir($absolutePath . DIRECTORY_SEPARATOR . 'controllers'),
                    'hasModels' => is_dir($absolutePath . DIRECTORY_SEPARATOR . 'models'),
                    'hasCommands' => is_dir($absolutePath . DIRECTORY_SEPARATOR . 'commands'),
                ];
            }
        }

        ksort($plugins);

        return [
            'projectRoot' => $projectRoot,
            'pluginsRoot' => $pluginsRoot,
            'exists' => is_dir($pluginsRoot),
            'plugins' => $plugins,
        ];
    }

    private function resolveRoot(string $projectRoot, KirbyRoots $roots, string $key, string $fallbackRelative): string
    {
        $value = $roots->get($key);
        if (is_string($value) && $value !== '') {
            return rtrim($value, DIRECTORY_SEPARATOR);
        }

        return rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $fallbackRelative);
    }

    /**
     * @return array<string, string> Map of root-relative path => absolute path
     */
    private function scanPhpFiles(string $root): array
    {
        $root = rtrim($root, DIRECTORY_SEPARATOR);
        if ($root === '' || !is_dir($root)) {
            return [];
        }

        $files = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        foreach ($iterator as $file) {
            if ($file->isFile() === false || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $absolutePath = $file->getPathname();
            $rootRelativePath = ltrim(substr($absolutePath, strlen($root)), DIRECTORY_SEPARATOR);
            $rootRelativePath = str_replace(DIRECTORY_SEPARATOR, '/', $rootRelativePath);

            $files[$rootRelativePath] = $absolutePath;
        }

        ksort($files);

        return $files;
    }

    private function stripPhpExtension(string $rootRelativePath): string
    {
        $rootRelativePath = preg_replace('/\\.php$/i', '', $rootRelativePath) ?? $rootRelativePath;
        return trim($rootRelativePath, '/');
    }

    /**
     * @return array{0: string, 1: string|null} name, representation
     */
    private function splitRepresentation(string $idStem): array
    {
        $parts = explode('.', $idStem);
        if (count($parts) < 2) {
            return [$idStem, null];
        }

        $last = $parts[count($parts) - 1];
        if (!in_array($last, self::COMMON_REPRESENTATIONS, true)) {
            return [$idStem, null];
        }

        $name = implode('.', array_slice($parts, 0, -1));
        if ($name === '') {
            return [$idStem, null];
        }

        return [$name, $last];
    }

    private function relativeToProject(string $projectRoot, string $absolutePath): string
    {
        $projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);
        $absolutePath = rtrim($absolutePath, DIRECTORY_SEPARATOR);

        if ($projectRoot !== '' && str_starts_with($absolutePath, $projectRoot . DIRECTORY_SEPARATOR)) {
            return ltrim(substr($absolutePath, strlen($projectRoot)), DIRECTORY_SEPARATOR);
        }

        return $absolutePath;
    }
}
