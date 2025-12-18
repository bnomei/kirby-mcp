<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Runtime;

use Kirby\CLI\CLI;
use Kirby\Plugin\Plugin;
use Throwable;

final class McpPluginsCommand extends McpRuntimeCommand
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
            'description' => 'Lists loaded Kirby plugins (runtime truth) with metadata and capability hints (structured output for MCP).',
            'args' => [
                'idsOnly' => [
                    'longPrefix' => 'ids-only',
                    'description' => 'Return only plugin ids (minimal payload).',
                    'noValue' => true,
                ],
                'cursor' => [
                    'longPrefix' => 'cursor',
                    'description' => 'Pagination cursor (0-based offset in sorted filtered plugins). Default: 0.',
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

            $pluginsRoot = $kirby->root('plugins');
            $pluginsRoot = is_string($pluginsRoot) ? rtrim($pluginsRoot, DIRECTORY_SEPARATOR) : null;

            $plugins = $kirby->plugins();

            $ids = [];
            foreach ($plugins as $id => $plugin) {
                if (!is_string($id) || $id === '') {
                    continue;
                }
                if (!$plugin instanceof Plugin) {
                    continue;
                }
                $ids[] = $id;
            }

            $ids = array_values(array_unique($ids));
            sort($ids);

            $total = count($ids);
            $pagedIds = $ids;
            if ($cursor > 0 || $limit > 0) {
                if ($cursor >= $total) {
                    $pagedIds = [];
                } elseif ($limit > 0) {
                    $pagedIds = array_slice($ids, $cursor, $limit);
                } else {
                    $pagedIds = array_slice($ids, $cursor);
                }
            }

            $returned = count($pagedIds);
            $nextCursor = null;
            $hasMore = false;
            if ($limit > 0 && $cursor + $returned < $total) {
                $nextCursor = $cursor + $returned;
                $hasMore = true;
            }

            $projectRoot = rtrim($cli->dir(), DIRECTORY_SEPARATOR);

            $items = [];
            foreach ($pagedIds as $id) {
                $plugin = $plugins[$id] ?? null;
                if (!$plugin instanceof Plugin) {
                    continue;
                }

                if ($idsOnly === true) {
                    $items[] = ['id' => $id];
                    continue;
                }

                $root = rtrim($plugin->root(), DIRECTORY_SEPARATOR);
                $dirName = basename($root);

                $relativeToPluginsRoot = null;
                if (is_string($pluginsRoot) && $pluginsRoot !== '' && str_starts_with($root, $pluginsRoot . DIRECTORY_SEPARATOR)) {
                    $relativeToPluginsRoot = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', substr($root, strlen($pluginsRoot))), '/');
                }

                $extends = $plugin->extends();
                $extendKeys = array_keys($extends);
                sort($extendKeys);

                $extendCounts = [];
                foreach ($extendKeys as $key) {
                    $value = $extends[$key] ?? null;
                    $extendCounts[$key] = is_array($value) ? count($value) : ($value === null ? 0 : 1);
                }

                $items[] = [
                    'id' => $id,
                    'dirName' => $dirName,
                    'plugin' => [
                        'name' => $plugin->name(),
                        'prefix' => $plugin->prefix(),
                        'version' => $plugin->version(),
                        'description' => $plugin->description(),
                        'link' => $plugin->link(),
                        'root' => $root,
                    ],
                    'extends' => [
                        'keys' => $extendKeys,
                        'counts' => $extendCounts,
                    ],
                    'hasIndexPhp' => is_file($root . DIRECTORY_SEPARATOR . 'index.php'),
                    'hasComposerJson' => is_file($root . DIRECTORY_SEPARATOR . 'composer.json'),
                    'hasPackageJson' => is_file($root . DIRECTORY_SEPARATOR . 'package.json'),
                    'hasBlueprints' => is_dir($root . DIRECTORY_SEPARATOR . 'blueprints'),
                    'hasSnippets' => is_dir($root . DIRECTORY_SEPARATOR . 'snippets'),
                    'hasTemplates' => is_dir($root . DIRECTORY_SEPARATOR . 'templates'),
                    'hasControllers' => is_dir($root . DIRECTORY_SEPARATOR . 'controllers'),
                    'hasModels' => is_dir($root . DIRECTORY_SEPARATOR . 'models'),
                    'hasCommands' => is_dir($root . DIRECTORY_SEPARATOR . 'commands'),
                    'activeSource' => 'file',
                    'sources' => ['file'],
                    'overriddenByFile' => false,
                    'file' => [
                        'active' => [
                            'absolutePath' => $root,
                        ],
                        'pluginsRoot' => is_string($pluginsRoot) && $pluginsRoot !== '' ? [
                            'absolutePath' => $root,
                            'relativeToPluginsRoot' => $relativeToPluginsRoot,
                        ] : null,
                        'extension' => null,
                    ],
                    'absolutePath' => $root,
                    'relativePath' => $projectRoot !== '' && str_starts_with($root, $projectRoot . DIRECTORY_SEPARATOR)
                        ? ltrim(substr($root, strlen($projectRoot)), DIRECTORY_SEPARATOR)
                        : $root,
                    'rootRelativePath' => $relativeToPluginsRoot,
                ];
            }

            self::emit($cli, [
                'ok' => true,
                'idsOnly' => $idsOnly,
                'pluginsRoot' => $pluginsRoot,
                'filters' => [],
                'pagination' => [
                    'cursor' => $cursor,
                    'limit' => $limit,
                    'nextCursor' => $nextCursor,
                    'hasMore' => $hasMore,
                    'returned' => $returned,
                    'total' => $total,
                ],
                'counts' => [
                    'total' => $total,
                    'returned' => $returned,
                ],
                'plugins' => $items,
                'errors' => [],
            ]);
        } catch (Throwable $exception) {
            self::emit($cli, [
                'ok' => false,
                'error' => self::errorArray($exception, self::traceForCli($cli, $exception)),
            ]);
        }
    }
}
