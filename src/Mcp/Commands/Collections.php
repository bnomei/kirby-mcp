<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Commands;

use Bnomei\KirbyMcp\Mcp\Support\ExtensionRegistryIndexer;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommand;
use Kirby\CLI\CLI;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;

final class Collections extends RuntimeCommand
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
            'description' => 'Lists named collection ids that Kirby can resolve (filesystem + extensions) with override hints (structured output for MCP).',
            'args' => [
                'idsOnly' => [
                    'longPrefix' => 'ids-only',
                    'description' => 'Return only collection ids (minimal payload).',
                    'noValue' => true,
                ],
                'activeSource' => [
                    'longPrefix' => 'active-source',
                    'description' => 'Filter by active source (file or extension).',
                ],
                'overriddenOnly' => [
                    'longPrefix' => 'overridden-only',
                    'description' => 'Only return collections where a filesystem collection overrides an extension.',
                    'noValue' => true,
                ],
                'cursor' => [
                    'longPrefix' => 'cursor',
                    'description' => 'Pagination cursor (0-based offset in sorted filtered collections). Default: 0.',
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
            $activeSource = $cli->arg('activeSource');
            $activeSource = is_string($activeSource) ? trim($activeSource) : null;

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

            $collectionsRoot = $kirby->root('collections');
            $collectionsRoot = is_string($collectionsRoot) ? rtrim($collectionsRoot, DIRECTORY_SEPARATOR) : null;

            $extensions = $kirby->extensions('collections');

            $files = is_string($collectionsRoot) && $collectionsRoot !== '' && is_dir($collectionsRoot)
                ? ExtensionRegistryIndexer::scanPhpFiles($collectionsRoot, static function (string $rootRelativePath): ?string {
                    $id = preg_replace('/\\.php$/i', '', $rootRelativePath) ?? $rootRelativePath;
                    $id = trim($id, '/');

                    return $id !== '' ? $id : null;
                })
                : [];

            $merged = ExtensionRegistryIndexer::merge($extensions, $files, static fn (mixed $value): array => self::extensionInfo($value));
            $filtered = ExtensionRegistryIndexer::filterAndPaginateItems(
                $merged['items'],
                $activeSource,
                $overriddenOnly,
                $cursor,
                $limit,
            );

            $collections = [];
            foreach ($filtered['items'] as $item) {
                $id = $item['id'];

                if ($idsOnly === true) {
                    $collections[] = ['id' => $id];
                    continue;
                }

                $fileInfo = $item['file'];
                $extensionInfo = $item['extension'];

                $collections[] = [
                    'id' => $id,
                    'name' => $id,
                    'activeSource' => $item['activeSource'],
                    'sources' => $item['sources'],
                    'overriddenByFile' => $item['overriddenByFile'],
                    'file' => [
                        'active' => is_string($item['activeAbsolutePath']) ? [
                            'absolutePath' => $item['activeAbsolutePath'],
                        ] : null,
                        'collectionsRoot' => is_array($fileInfo) ? [
                            'absolutePath' => $fileInfo['absolutePath'],
                            'relativeToCollectionsRoot' => $fileInfo['relativeToRoot'],
                        ] : null,
                        'extension' => is_array($extensionInfo) ? [
                            'kind' => $extensionInfo['kind'],
                            'absolutePath' => $extensionInfo['absolutePath'] ?? null,
                        ] : null,
                    ],
                ];
            }

            self::emit($cli, [
                'ok' => true,
                'idsOnly' => $idsOnly,
                'collectionsRoot' => $collectionsRoot,
                'filters' => $filtered['filters'],
                'pagination' => $filtered['pagination'],
                'counts' => array_merge($merged['counts'], [
                    'filtered' => $filtered['pagination']['total'],
                    'returned' => $filtered['pagination']['returned'],
                ]),
                'collections' => $collections,
            ]);
        } catch (Throwable $exception) {
            self::emit($cli, [
                'ok' => false,
                'error' => self::errorArray($exception, self::traceForCli($cli, $exception)),
            ]);
        }
    }

    /**
     * @return array{kind:'file'|'array'|'callable'|'unknown', absolutePath:string|null}
     */
    private static function extensionInfo(mixed $value): array
    {
        $basic = ExtensionRegistryIndexer::extensionInfoBasic($value);

        $absolutePath = $basic['absolutePath'] ?? null;
        if (is_string($absolutePath) && $absolutePath !== '') {
            return $basic;
        }

        if (!is_callable($value)) {
            return $basic;
        }

        $ref = null;
        try {
            if ($value instanceof \Closure) {
                $ref = new ReflectionFunction($value);
            } elseif (is_array($value) && count($value) === 2) {
                $target = $value[0] ?? null;
                $method = $value[1] ?? null;
                if (is_object($target) && is_string($method) && $method !== '') {
                    $ref = new ReflectionMethod($target, $method);
                } elseif (is_string($target) && $target !== '' && is_string($method) && $method !== '') {
                    $ref = new ReflectionMethod($target, $method);
                }
            } elseif (is_object($value) && method_exists($value, '__invoke')) {
                $ref = new ReflectionMethod($value, '__invoke');
            } elseif (is_string($value) && $value !== '') {
                $ref = new ReflectionFunction($value);
            }
        } catch (Throwable) {
            $ref = null;
        }

        $file = null;
        if ($ref instanceof \ReflectionFunctionAbstract) {
            $file = $ref->getFileName();
        }

        if (is_string($file) && $file !== '') {
            $basic['absolutePath'] = $file;
        }

        return $basic;
    }
}
