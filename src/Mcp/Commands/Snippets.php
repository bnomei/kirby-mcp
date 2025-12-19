<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Commands;

use Bnomei\KirbyMcp\Mcp\Support\ExtensionRegistryIndexer;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommand;
use Kirby\CLI\CLI;
use Throwable;

final class Snippets extends RuntimeCommand
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
            'description' => 'Lists snippet ids that Kirby can resolve (filesystem + extensions) with override hints (structured output for MCP).',
            'args' => [
                'idsOnly' => [
                    'longPrefix' => 'ids-only',
                    'description' => 'Return only snippet ids (minimal payload).',
                    'noValue' => true,
                ],
                'activeSource' => [
                    'longPrefix' => 'active-source',
                    'description' => 'Filter by active source (file or extension).',
                ],
                'overriddenOnly' => [
                    'longPrefix' => 'overridden-only',
                    'description' => 'Only return snippets where a filesystem snippet overrides an extension.',
                    'noValue' => true,
                ],
                'cursor' => [
                    'longPrefix' => 'cursor',
                    'description' => 'Pagination cursor (0-based offset in sorted filtered snippets). Default: 0.',
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

            $snippetsRoot = $kirby->root('snippets');
            $snippetsRoot = is_string($snippetsRoot) ? rtrim($snippetsRoot, DIRECTORY_SEPARATOR) : null;

            $extensions = $kirby->extensions('snippets');

            $files = is_string($snippetsRoot) && $snippetsRoot !== '' && is_dir($snippetsRoot)
                ? ExtensionRegistryIndexer::scanPhpFiles($snippetsRoot, static function (string $rootRelativePath): ?string {
                    $id = preg_replace('/\\.php$/i', '', $rootRelativePath) ?? $rootRelativePath;
                    $id = trim($id, '/');

                    return $id !== '' ? $id : null;
                })
                : [];

            $merged = ExtensionRegistryIndexer::merge($extensions, $files, [ExtensionRegistryIndexer::class, 'extensionInfoBasic']);
            $filtered = ExtensionRegistryIndexer::filterAndPaginateItems(
                $merged['items'],
                $activeSource,
                $overriddenOnly,
                $cursor,
                $limit,
            );

            $snippets = [];
            foreach ($filtered['items'] as $item) {
                $id = $item['id'];

                if ($idsOnly === true) {
                    $snippets[] = ['id' => $id];
                    continue;
                }

                $fileInfo = $item['file'];
                $extensionInfo = $item['extension'];

                $snippets[] = [
                    'id' => $id,
                    'name' => $id,
                    'activeSource' => $item['activeSource'],
                    'sources' => $item['sources'],
                    'overriddenByFile' => $item['overriddenByFile'],
                    'file' => [
                        'active' => is_string($item['activeAbsolutePath']) ? [
                            'absolutePath' => $item['activeAbsolutePath'],
                        ] : null,
                        'snippetsRoot' => is_array($fileInfo) ? [
                            'absolutePath' => $fileInfo['absolutePath'],
                            'relativeToSnippetsRoot' => $fileInfo['relativeToRoot'],
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
                'snippetsRoot' => $snippetsRoot,
                'filters' => $filtered['filters'],
                'pagination' => $filtered['pagination'],
                'counts' => array_merge($merged['counts'], [
                    'filtered' => $filtered['pagination']['total'],
                    'returned' => $filtered['pagination']['returned'],
                ]),
                'snippets' => $snippets,
            ]);
        } catch (Throwable $exception) {
            self::emit($cli, [
                'ok' => false,
                'error' => self::errorArray($exception, self::traceForCli($cli, $exception)),
            ]);
        }
    }
}
