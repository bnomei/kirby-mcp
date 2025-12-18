<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Runtime;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class McpExtensionRegistryIndexer
{
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
     * @param callable(string): (string|null) $idFromRootRelativePath
     * @return array<string, array{absolutePath:string, relativeToRoot:string}>
     */
    public static function scanPhpFiles(string $root, callable $idFromRootRelativePath): array
    {
        $root = rtrim($root, DIRECTORY_SEPARATOR);
        if ($root === '' || !is_dir($root)) {
            return [];
        }

        $files = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $absolutePath = $file->getPathname();
            $rootRelativePath = ltrim(substr($absolutePath, strlen($root)), DIRECTORY_SEPARATOR);
            $rootRelativePath = str_replace(DIRECTORY_SEPARATOR, '/', $rootRelativePath);

            $id = $idFromRootRelativePath($rootRelativePath);
            if (!is_string($id)) {
                continue;
            }

            $id = trim($id);
            if ($id === '') {
                continue;
            }

            if (!isset($files[$id])) {
                $files[$id] = [
                    'absolutePath' => $absolutePath,
                    'relativeToRoot' => $rootRelativePath,
                ];
            }
        }

        ksort($files);

        return $files;
    }

    /**
     * @param array<string, mixed> $extensions
     * @param array<string, array{absolutePath:string, relativeToRoot:string}> $files
     * @param callable(mixed): array{kind:string, absolutePath?:string|null} $extensionInfo
     * @return array{
     *   counts: array{extensions:int, files:int, total:int, overriddenByFile:int},
     *   items: array<int, array{
     *     id: string,
     *     activeSource: 'file'|'extension'|null,
     *     sources: array<int, 'extension'|'file'>,
     *     overriddenByFile: bool,
     *     activeAbsolutePath: string|null,
     *     file: array{absolutePath:string, relativeToRoot:string}|null,
     *     extension: array{kind:string, absolutePath?:string|null}|null
     *   }>
     * }
     */
    public static function merge(array $extensions, array $files, callable $extensionInfo): array
    {
        $extensionIds = array_values(array_filter(array_keys($extensions), static fn ($id) => is_string($id) && $id !== ''));
        sort($extensionIds);

        $fileIds = array_values(array_filter(array_keys($files), static fn ($id) => is_string($id) && $id !== ''));
        sort($fileIds);

        $allIds = array_values(array_unique(array_merge($extensionIds, $fileIds)));
        sort($allIds);

        $items = [];
        $overriddenCount = 0;

        $extensionSet = array_fill_keys($extensionIds, true);

        foreach ($allIds as $id) {
            $fromExtension = isset($extensionSet[$id]);
            $fileInfo = $files[$id] ?? null;
            $fromFile = is_array($fileInfo) && is_string($fileInfo['absolutePath'] ?? null) && ($fileInfo['absolutePath'] ?? '') !== '';
            $overriddenByFile = $fromExtension && $fromFile;

            if ($overriddenByFile) {
                $overriddenCount++;
            }

            $sources = [];
            if ($fromExtension) {
                $sources[] = 'extension';
            }
            if ($fromFile) {
                $sources[] = 'file';
            }

            $extension = $fromExtension ? ($extensions[$id] ?? null) : null;
            $extensionDetails = $fromExtension ? $extensionInfo($extension) : null;

            if ($fromExtension === true) {
                if (!is_array($extensionDetails) || !is_string($extensionDetails['kind'] ?? null)) {
                    $extensionDetails = [
                        'kind' => 'unknown',
                        'absolutePath' => null,
                    ];
                }
            } else {
                $extensionDetails = null;
            }

            $activeSource = $fromFile ? 'file' : ($fromExtension ? 'extension' : null);

            $activeAbsolutePath = null;
            if ($activeSource === 'file') {
                $activeAbsolutePath = is_string($fileInfo['absolutePath'] ?? null) ? $fileInfo['absolutePath'] : null;
            } elseif ($activeSource === 'extension') {
                $activeAbsolutePath = is_string($extensionDetails['absolutePath'] ?? null) ? $extensionDetails['absolutePath'] : null;
            }

            $items[] = [
                'id' => $id,
                'activeSource' => $activeSource,
                'sources' => $sources,
                'overriddenByFile' => $overriddenByFile,
                'activeAbsolutePath' => $activeAbsolutePath,
                'file' => $fromFile ? $fileInfo : null,
                'extension' => $extensionDetails,
            ];
        }

        return [
            'counts' => [
                'extensions' => count($extensionIds),
                'files' => count($fileIds),
                'total' => count($allIds),
                'overriddenByFile' => $overriddenCount,
            ],
            'items' => $items,
        ];
    }

    /**
     * @param array<int, array{
     *   id: string,
     *   activeSource: 'file'|'extension'|null,
     *   sources: array<int, 'extension'|'file'>,
     *   overriddenByFile: bool,
     *   activeAbsolutePath: string|null,
     *   file: array{absolutePath:string, relativeToRoot:string}|null,
     *   extension: array{kind:string, absolutePath?:string|null}|null
     * }> $items
     *
     * @return array{
     *   items: array<int, array{
     *     id: string,
     *     activeSource: 'file'|'extension'|null,
     *     sources: array<int, 'extension'|'file'>,
     *     overriddenByFile: bool,
     *     activeAbsolutePath: string|null,
     *     file: array{absolutePath:string, relativeToRoot:string}|null,
     *     extension: array{kind:string, absolutePath?:string|null}|null
     *   }>,
     *   filters: array{activeSource?:'file'|'extension', overriddenOnly?:bool},
     *   pagination: array{cursor:int, limit:int, nextCursor:int|null, hasMore:bool, returned:int, total:int}
     * }
     */
    public static function filterAndPaginateItems(
        array $items,
        ?string $activeSourceFilter = null,
        bool $overriddenOnly = false,
        int $cursor = 0,
        int $limit = 0,
    ): array {
        $activeSourceFilter = is_string($activeSourceFilter) ? strtolower(trim($activeSourceFilter)) : null;
        if ($activeSourceFilter === '') {
            $activeSourceFilter = null;
        }
        if ($activeSourceFilter !== null && $activeSourceFilter !== 'file' && $activeSourceFilter !== 'extension') {
            $activeSourceFilter = null;
        }

        if ($cursor < 0) {
            $cursor = 0;
        }

        if ($limit < 0) {
            $limit = 0;
        }

        $filtered = [];
        foreach ($items as $item) {
            $activeSource = $item['activeSource'] ?? null;
            if ($activeSourceFilter !== null && $activeSource !== $activeSourceFilter) {
                continue;
            }

            $overriddenByFile = $item['overriddenByFile'] ?? false;
            if ($overriddenOnly === true && $overriddenByFile !== true) {
                continue;
            }

            $filtered[] = $item;
        }

        $total = count($filtered);

        $paged = $filtered;
        if ($cursor > 0 || $limit > 0) {
            if ($cursor >= $total) {
                $paged = [];
            } elseif ($limit > 0) {
                $paged = array_slice($filtered, $cursor, $limit);
            } else {
                $paged = array_slice($filtered, $cursor);
            }
        }

        $returned = count($paged);
        $nextCursor = null;
        $hasMore = false;
        if ($limit > 0 && $cursor + $returned < $total) {
            $nextCursor = $cursor + $returned;
            $hasMore = true;
        }

        $filters = [];
        if ($activeSourceFilter !== null) {
            /** @var 'file'|'extension' $activeSourceFilter */
            $filters['activeSource'] = $activeSourceFilter;
        }
        if ($overriddenOnly === true) {
            $filters['overriddenOnly'] = true;
        }

        return [
            'items' => $paged,
            'filters' => $filters,
            'pagination' => [
                'cursor' => $cursor,
                'limit' => $limit,
                'nextCursor' => $nextCursor,
                'hasMore' => $hasMore,
                'returned' => $returned,
                'total' => $total,
            ],
        ];
    }

    /**
     * @return array{0:string,1:string|null} name, representation
     */
    public static function splitRepresentation(string $id): array
    {
        $parts = explode('.', $id);
        if (count($parts) < 2) {
            return [$id, null];
        }

        $last = $parts[count($parts) - 1];
        if (!in_array($last, self::COMMON_REPRESENTATIONS, true)) {
            return [$id, null];
        }

        $name = implode('.', array_slice($parts, 0, -1));
        if ($name === '') {
            return [$id, null];
        }

        return [$name, $last];
    }

    /**
     * @return array{kind:'file'|'array'|'callable'|'unknown', absolutePath:string|null}
     */
    public static function extensionInfoBasic(mixed $value): array
    {
        $kind = 'unknown';
        $absolutePath = null;

        if (is_string($value)) {
            $kind = 'file';
            $absolutePath = $value;
        } elseif (is_array($value)) {
            $kind = 'array';
        } elseif (is_callable($value)) {
            $kind = 'callable';
        }

        return [
            'kind' => $kind,
            'absolutePath' => $absolutePath,
        ];
    }
}
