<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Commands;

use Bnomei\KirbyMcp\Mcp\Support\ExtensionRegistryIndexer;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommand;
use Kirby\CLI\CLI;
use Throwable;

final class Controllers extends RuntimeCommand
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
            'description' => 'Lists controller ids that Kirby can resolve (filesystem + extensions) with override hints (structured output for MCP).',
            'args' => [
                'idsOnly' => [
                    'longPrefix' => 'ids-only',
                    'description' => 'Return only controller ids (minimal payload).',
                    'noValue' => true,
                ],
                'activeSource' => [
                    'longPrefix' => 'active-source',
                    'description' => 'Filter by active source (file or extension).',
                ],
                'overriddenOnly' => [
                    'longPrefix' => 'overridden-only',
                    'description' => 'Only return controllers where a filesystem controller overrides an extension.',
                    'noValue' => true,
                ],
                'cursor' => [
                    'longPrefix' => 'cursor',
                    'description' => 'Pagination cursor (0-based offset in sorted filtered controllers). Default: 0.',
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

            $controllersRoot = $kirby->root('controllers');
            $controllersRoot = is_string($controllersRoot) ? rtrim($controllersRoot, DIRECTORY_SEPARATOR) : null;

            $extensions = $kirby->extensions('controllers');

            $files = is_string($controllersRoot) && $controllersRoot !== '' && is_dir($controllersRoot)
                ? ExtensionRegistryIndexer::scanPhpFiles($controllersRoot, static function (string $rootRelativePath): ?string {
                    $stem = preg_replace('/\\.php$/i', '', $rootRelativePath) ?? $rootRelativePath;
                    $idStem = str_replace('/', '.', trim($stem, '/'));
                    $idStem = trim($idStem, '.');
                    if ($idStem === '') {
                        return null;
                    }

                    [$name, $representation] = ExtensionRegistryIndexer::splitRepresentation($idStem);

                    return $representation !== null ? $name . '.' . $representation : $name;
                })
                : [];

            $merged = ExtensionRegistryIndexer::merge($extensions, $files, static function (mixed $value): array {
                return [
                    'kind' => self::extensionKind($value),
                    'absolutePath' => null,
                ];
            });
            $filtered = ExtensionRegistryIndexer::filterAndPaginateItems(
                $merged['items'],
                $activeSource,
                $overriddenOnly,
                $cursor,
                $limit,
            );

            $controllers = [];
            foreach ($filtered['items'] as $item) {
                $id = $item['id'];

                if ($idsOnly === true) {
                    $controllers[] = ['id' => $id];
                    continue;
                }

                $fileInfo = $item['file'];
                $extensionInfo = $item['extension'];

                [$name, $representation] = ExtensionRegistryIndexer::splitRepresentation($id);

                $controllers[] = [
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
                        'controllersRoot' => is_array($fileInfo) ? [
                            'absolutePath' => $fileInfo['absolutePath'],
                            'relativeToControllersRoot' => $fileInfo['relativeToRoot'],
                        ] : null,
                        'extension' => is_array($extensionInfo) ? [
                            'kind' => $extensionInfo['kind'],
                        ] : null,
                    ],
                ];
            }

            self::emit($cli, [
                'ok' => true,
                'idsOnly' => $idsOnly,
                'controllersRoot' => $controllersRoot,
                'filters' => $filtered['filters'],
                'pagination' => $filtered['pagination'],
                'counts' => array_merge($merged['counts'], [
                    'filtered' => $filtered['pagination']['total'],
                    'returned' => $filtered['pagination']['returned'],
                ]),
                'controllers' => $controllers,
            ]);
        } catch (Throwable $exception) {
            self::emit($cli, [
                'ok' => false,
                'error' => self::errorArray($exception, self::traceForCli($cli, $exception)),
            ]);
        }
    }
    private static function extensionKind(mixed $value): string
    {
        if ($value instanceof \Kirby\Toolkit\Controller) {
            return 'controller';
        }

        if (is_callable($value)) {
            return 'callable';
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_string($value)) {
            return 'string';
        }

        if (is_object($value)) {
            return 'object';
        }

        return 'unknown';
    }

}
