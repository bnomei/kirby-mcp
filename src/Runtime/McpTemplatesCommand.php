<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Runtime;

use Kirby\CLI\CLI;
use Throwable;

final class McpTemplatesCommand extends McpRuntimeCommand
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
            'description' => 'Lists template ids that Kirby can resolve (filesystem + extensions) with override hints (structured output for MCP).',
            'args' => [
                'idsOnly' => [
                    'longPrefix' => 'ids-only',
                    'description' => 'Return only template ids (minimal payload).',
                    'noValue' => true,
                ],
                'activeSource' => [
                    'longPrefix' => 'active-source',
                    'description' => 'Filter by active source (file or extension).',
                ],
                'overriddenOnly' => [
                    'longPrefix' => 'overridden-only',
                    'description' => 'Only return templates where a filesystem template overrides an extension.',
                    'noValue' => true,
                ],
                'cursor' => [
                    'longPrefix' => 'cursor',
                    'description' => 'Pagination cursor (0-based offset in sorted filtered templates). Default: 0.',
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

            $templatesRoot = $kirby->root('templates');
            $templatesRoot = is_string($templatesRoot) ? rtrim($templatesRoot, DIRECTORY_SEPARATOR) : null;

            $extensions = $kirby->extensions('templates');

            $files = is_string($templatesRoot) && $templatesRoot !== '' && is_dir($templatesRoot)
                ? McpExtensionRegistryIndexer::scanPhpFiles($templatesRoot, static function (string $rootRelativePath): ?string {
                    $stem = preg_replace('/\\.php$/i', '', $rootRelativePath) ?? $rootRelativePath;
                    $idStem = str_replace('/', '.', trim($stem, '/'));
                    $idStem = trim($idStem, '.');
                    if ($idStem === '') {
                        return null;
                    }

                    [$name, $representation] = McpExtensionRegistryIndexer::splitRepresentation($idStem);

                    return $representation !== null ? $name . '.' . $representation : $name;
                })
                : [];

            $merged = McpExtensionRegistryIndexer::merge($extensions, $files, [McpExtensionRegistryIndexer::class, 'extensionInfoBasic']);
            $filtered = McpExtensionRegistryIndexer::filterAndPaginateItems(
                $merged['items'],
                $activeSource,
                $overriddenOnly,
                $cursor,
                $limit,
            );

            $templates = [];
            foreach ($filtered['items'] as $item) {
                $id = $item['id'];

                if ($idsOnly === true) {
                    $templates[] = ['id' => $id];
                    continue;
                }

                $fileInfo = $item['file'];
                $extensionInfo = $item['extension'];

                [$name, $representation] = McpExtensionRegistryIndexer::splitRepresentation($id);

                $templates[] = [
                    'id' => $id,
                    'name' => $name,
                    'representation' => $representation,
                    'activeSource' => $item['activeSource'],
                    'sources' => $item['sources'],
                    'overriddenByFile' => $item['overriddenByFile'],
                    'file' => [
                        'active' => is_string($item['activeAbsolutePath']) ? [
                            'absolutePath' => $item['activeAbsolutePath'],
                        ] : null,
                        'templatesRoot' => is_array($fileInfo) ? [
                            'absolutePath' => $fileInfo['absolutePath'],
                            'relativeToTemplatesRoot' => $fileInfo['relativeToRoot'],
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
                'templatesRoot' => $templatesRoot,
                'filters' => $filtered['filters'],
                'pagination' => $filtered['pagination'],
                'counts' => array_merge($merged['counts'], [
                    'filtered' => $filtered['pagination']['total'],
                    'returned' => $filtered['pagination']['returned'],
                ]),
                'templates' => $templates,
            ]);
        } catch (Throwable $exception) {
            self::emit($cli, [
                'ok' => false,
                'error' => self::errorArray($exception, self::traceForCli($cli, $exception)),
            ]);
        }
    }
}
