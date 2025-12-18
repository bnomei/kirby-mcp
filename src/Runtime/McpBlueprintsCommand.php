<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Runtime;

use Kirby\CLI\CLI;
use Kirby\Cms\Blueprint;
use Kirby\Data\Yaml;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

final class McpBlueprintsCommand extends McpRuntimeCommand
{
    /**
     * @return array{
     *   description: string,
     *   args: array<string, mixed>,
     *   command: callable(CLI): void
     * }
     */
    public static function definition(): array
    {
        return [
            'description' => 'Lists blueprint ids that Kirby can load (filesystem + extensions) with override hints (structured output for MCP).',
            'args' => [
                'idsOnly' => [
                    'longPrefix' => 'ids-only',
                    'description' => 'Return only blueprint ids (minimal payload).',
                    'noValue' => true,
                ],
                'withData' => [
                    'longPrefix' => 'with-data',
                    'description' => 'Include parsed blueprint data (Kirby Blueprint::load) and derived displayName.',
                    'noValue' => true,
                ],
                'withDisplayName' => [
                    'longPrefix' => 'with-display-name',
                    'description' => 'Include derived displayName (title/name/label) without including full blueprint data.',
                    'noValue' => true,
                ],
                'type' => [
                    'longPrefix' => 'type',
                    'description' => 'Filter by blueprint type/prefix (e.g. pages, site, sections). Comma-separated allowed.',
                ],
                'activeSource' => [
                    'longPrefix' => 'active-source',
                    'description' => 'Filter by active source (file or extension).',
                ],
                'overriddenOnly' => [
                    'longPrefix' => 'overridden-only',
                    'description' => 'Only return blueprints where a filesystem blueprint overrides an extension.',
                    'noValue' => true,
                ],
                'cursor' => [
                    'longPrefix' => 'cursor',
                    'description' => 'Pagination cursor (0-based offset in sorted filtered blueprints). Default: 0.',
                ],
                'limit' => [
                    'longPrefix' => 'limit',
                    'description' => 'Pagination limit (0 means no limit). Default: 0.',
                ],
            ],
            'command' => [self::class, 'run'],
        ];
    }

    public static function run(CLI $cli): void
    {
        $kirby = self::kirbyOrEmitError($cli);
        if ($kirby === null) {
            return;
        }

        try {
            $idsOnly = $cli->arg('idsOnly') === true;
            $withData = $cli->arg('withData') === true;
            $withDisplayName = $cli->arg('withDisplayName') === true;

            if ($idsOnly === true) {
                $withData = false;
                $withDisplayName = false;
            }

            $blueprintsRoot = $kirby->root('blueprints');
            $blueprintsRoot = is_string($blueprintsRoot) ? rtrim($blueprintsRoot, DIRECTORY_SEPARATOR) : null;

            $extensions = $kirby->extensions('blueprints');
            $extensionIds = array_keys($extensions);
            sort($extensionIds);

            $files = is_string($blueprintsRoot) && $blueprintsRoot !== '' && is_dir($blueprintsRoot)
                ? self::scanBlueprintFiles($blueprintsRoot)
                : [];

            $fileIds = array_keys($files);
            sort($fileIds);

            $allIds = array_values(array_unique(array_merge($extensionIds, $fileIds)));
            sort($allIds);

            $typeFilter = $cli->arg('type');
            $allowedTypes = null;
            if (is_string($typeFilter) && trim($typeFilter) !== '') {
                $parts = array_filter(array_map('trim', explode(',', $typeFilter)));
                if ($parts !== []) {
                    $allowedTypes = array_fill_keys($parts, true);
                }
            }

            $activeSourceFilter = $cli->arg('activeSource');
            $activeSourceFilter = is_string($activeSourceFilter) ? strtolower(trim($activeSourceFilter)) : null;
            if ($activeSourceFilter === '') {
                $activeSourceFilter = null;
            }
            if ($activeSourceFilter !== null && $activeSourceFilter !== 'file' && $activeSourceFilter !== 'extension') {
                $activeSourceFilter = null;
            }

            $overriddenOnly = $cli->arg('overriddenOnly') === true;

            $cursorRaw = $cli->arg('cursor');
            $cursor = is_numeric($cursorRaw) ? (int) $cursorRaw : 0;
            if ($cursor < 0) {
                $cursor = 0;
            }

            $limitRaw = $cli->arg('limit');
            $limit = is_numeric($limitRaw) ? (int) $limitRaw : 0;
            if ($limit < 0) {
                $limit = 0;
            }

            $filteredIds = [];
            foreach ($allIds as $id) {
                $type = self::blueprintType($id);
                if (is_array($allowedTypes) && !isset($allowedTypes[$type])) {
                    continue;
                }

                $fromFile = isset($files[$id]);
                $fromExtension = array_key_exists($id, $extensions);

                $activeSource = $fromFile ? 'file' : ($fromExtension ? 'extension' : null);
                if ($activeSourceFilter !== null && $activeSource !== $activeSourceFilter) {
                    continue;
                }

                $overriddenByFile = $fromExtension && $fromFile;
                if ($overriddenOnly === true && $overriddenByFile === false) {
                    continue;
                }

                $filteredIds[] = $id;
            }

            $filteredTotal = count($filteredIds);
            $pagedIds = $filteredIds;
            if ($cursor > 0 || $limit > 0) {
                if ($cursor >= $filteredTotal) {
                    $pagedIds = [];
                } elseif ($limit > 0) {
                    $pagedIds = array_slice($filteredIds, $cursor, $limit);
                } else {
                    $pagedIds = array_slice($filteredIds, $cursor);
                }
            }

            $returnedCount = count($pagedIds);
            $nextCursor = null;
            $hasMore = false;
            if ($limit > 0 && $cursor + $returnedCount < $filteredTotal) {
                $nextCursor = $cursor + $returnedCount;
                $hasMore = true;
            }

            $blueprints = [];
            $overriddenCount = 0;
            $dataCount = 0;
            $loadErrors = [];

            $extensionSet = array_fill_keys($extensionIds, true);
            foreach ($pagedIds as $id) {
                $fromExtension = isset($extensionSet[$id]);
                $filePath = $files[$id] ?? null;
                $fromFile = is_string($filePath);
                $overriddenByFile = $fromExtension && $fromFile;

                if ($overriddenByFile) {
                    $overriddenCount++;
                }

                $type = self::blueprintType($id);

                $relativeToRoot = null;
                if ($fromFile && is_string($blueprintsRoot) && $blueprintsRoot !== '') {
                    $relativeToRoot = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', substr($filePath, strlen($blueprintsRoot))), '/');
                }

                $sources = [];
                if ($fromExtension) {
                    $sources[] = 'extension';
                }
                if ($fromFile) {
                    $sources[] = 'file';
                }

                $extension = $fromExtension ? ($extensions[$id] ?? null) : null;
                $extensionKind = $fromExtension ? self::extensionKind($extension) : null;
                $extensionPath = null;
                if (is_string($extension) && is_file($extension)) {
                    $extensionPath = $extension;
                }

                $activeSource = $fromFile ? 'file' : ($fromExtension ? 'extension' : null);
                $activeFile = $activeSource === 'file' ? $filePath : $extensionPath;

                $data = null;
                $displayName = null;
                $displayNameSource = null;
                $dataError = null;
                if ($withData === true) {
                    try {
                        $data = Blueprint::load($id);
                        [$displayName, $displayNameSource] = self::deriveDisplayName($id, $data, is_string($activeFile) ? $activeFile : null);
                        $dataCount++;
                    } catch (Throwable $exception) {
                        $dataError = [
                            'class' => $exception::class,
                            'message' => $exception->getMessage(),
                            'code' => $exception->getCode(),
                        ];
                        $loadErrors[] = [
                            'id' => $id,
                            'error' => $dataError,
                        ];
                    }
                } elseif ($withDisplayName === true) {
                    [$displayName, $displayNameSource] = self::deriveDisplayNameForListing($id, $extension, is_string($activeFile) ? $activeFile : null);
                }

                if ($idsOnly === true) {
                    $blueprints[] = ['id' => $id];
                } else {
                    $blueprints[] = [
                        'id' => $id,
                        'type' => $type,
                        'activeSource' => $activeSource,
                        'sources' => $sources,
                        'overriddenByFile' => $overriddenByFile,
                        'file' => [
                            'active' => $activeFile !== null ? [
                                'absolutePath' => $activeFile,
                            ] : null,
                            'blueprintsRoot' => $fromFile ? [
                                'absolutePath' => $filePath,
                                'relativeToBlueprintsRoot' => $relativeToRoot,
                            ] : null,
                            'extension' => $fromExtension ? [
                                'kind' => $extensionKind,
                                'absolutePath' => $extensionPath,
                            ] : null,
                        ],
                        'displayName' => $displayName,
                        'displayNameSource' => $displayNameSource,
                        'data' => $data,
                        'dataError' => $dataError,
                    ];
                }
            }

            $payload = [
                'ok' => true,
                'idsOnly' => $idsOnly,
                'withData' => $withData,
                'withDisplayName' => $withDisplayName,
                'blueprintsRoot' => $blueprintsRoot,
                'filters' => array_filter([
                    'type' => $allowedTypes !== null ? array_keys($allowedTypes) : null,
                    'activeSource' => $activeSourceFilter,
                    'overriddenOnly' => $overriddenOnly,
                ], static fn ($value) => $value !== null),
                'pagination' => [
                    'cursor' => $cursor,
                    'limit' => $limit,
                    'nextCursor' => $nextCursor,
                    'hasMore' => $hasMore,
                    'returned' => $returnedCount,
                    'total' => $filteredTotal,
                ],
                'counts' => [
                    'extensions' => count($extensionIds),
                    'files' => count($fileIds),
                    'total' => count($allIds),
                    'filtered' => $filteredTotal,
                    'returned' => $returnedCount,
                    'overriddenByFile' => $overriddenCount,
                    'withData' => $dataCount,
                    'loadErrors' => count($loadErrors),
                ],
                'blueprints' => $blueprints,
                'errors' => $loadErrors,
            ];
        } catch (Throwable $exception) {
            $payload = [
                'ok' => false,
                'error' => self::errorArray($exception, self::traceForCli($cli, $exception)),
            ];
        }

        self::emit($cli, $payload);
    }

    /**
     * @return array<string, string> Map blueprint id => absolute path
     */
    private static function scanBlueprintFiles(string $root): array
    {
        $root = rtrim($root, DIRECTORY_SEPARATOR);
        if ($root === '' || !is_dir($root)) {
            return [];
        }

        $files = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if ($ext !== 'yml' && $ext !== 'yaml') {
                continue;
            }

            $absolutePath = $file->getPathname();
            $relativePath = ltrim(substr($absolutePath, strlen($root)), DIRECTORY_SEPARATOR);
            $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

            $id = preg_replace('/\\.(yml|yaml)$/i', '', $relativePath) ?? $relativePath;
            $id = trim($id, '/');

            if ($id === '') {
                continue;
            }

            if (!isset($files[$id])) {
                $files[$id] = $absolutePath;
                continue;
            }

            // Prefer `.yml` over `.yaml` when both exist.
            if (str_ends_with($files[$id], '.yml') === false && str_ends_with($absolutePath, '.yml') === true) {
                $files[$id] = $absolutePath;
            }
        }

        ksort($files);

        return $files;
    }

    private static function blueprintType(string $id): string
    {
        if (!str_contains($id, '/')) {
            return $id;
        }

        $first = explode('/', $id, 2)[0];
        return $first !== '' ? $first : 'unknown';
    }

    private static function extensionKind(mixed $value): string
    {
        if (is_string($value)) {
            return 'file';
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_callable($value)) {
            return 'callable';
        }

        return 'unknown';
    }

    /**
     * @return array{0:string,1:'title'|'name'|'label'|'id'}
     */
    private static function deriveDisplayNameForListing(string $id, mixed $extension, ?string $yamlFile = null): array
    {
        if (is_string($yamlFile) && $yamlFile !== '' && (str_ends_with($yamlFile, '.yml') || str_ends_with($yamlFile, '.yaml'))) {
            try {
                $raw = Yaml::read($yamlFile);
                if (is_array($raw)) {
                    return self::deriveDisplayNameFromArray($id, $raw);
                }
            } catch (Throwable) {
                // ignore and fall back to extension array/id
            }
        }

        if (is_array($extension)) {
            return self::deriveDisplayNameFromArray($id, $extension);
        }

        return self::deriveDisplayNameFromArray($id, []);
    }

    /**
     * @param array<mixed> $data
     * @return array{0:string,1:'title'|'name'|'label'|'id'}
     */
    private static function deriveDisplayName(string $id, array $data, ?string $yamlFile = null): array
    {
        if (is_string($yamlFile) && $yamlFile !== '' && (str_ends_with($yamlFile, '.yml') || str_ends_with($yamlFile, '.yaml'))) {
            try {
                $raw = Yaml::read($yamlFile);
                if (is_array($raw)) {
                    return self::deriveDisplayNameFromArray($id, $raw);
                }
            } catch (Throwable) {
                // fall back to resolved blueprint data
            }
        }

        return self::deriveDisplayNameFromArray($id, $data);
    }

    /**
     * @param array<mixed> $data
     * @return array{0:string,1:'title'|'name'|'label'|'id'}
     */
    private static function deriveDisplayNameFromArray(string $id, array $data): array
    {
        $fallback = $id;
        $lastSlash = strrpos($fallback, '/');
        if ($lastSlash !== false) {
            $fallback = substr($fallback, $lastSlash + 1);
        }

        $fallback = trim($fallback);
        if ($fallback === '') {
            $fallback = $id;
        }

        foreach (['title', 'name', 'label'] as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            $string = self::stringFromNameValue($value);
            if ($string !== null) {
                /** @var 'title'|'name'|'label' $key */
                return [$string, $key];
            }
        }

        return [$fallback, 'id'];
    }

    private static function stringFromNameValue(mixed $value): ?string
    {
        if (is_string($value)) {
            $value = trim($value);
            return $value !== '' ? $value : null;
        }

        if (!is_array($value)) {
            return null;
        }

        $en = $value['en'] ?? null;
        if (is_string($en) && trim($en) !== '') {
            return trim($en);
        }

        foreach ($value as $v) {
            if (!is_string($v)) {
                continue;
            }

            $v = trim($v);
            if ($v !== '') {
                return $v;
            }
        }

        return null;
    }

}
