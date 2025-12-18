<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Runtime;

use Kirby\CLI\CLI;
use ReflectionClass;
use Throwable;

final class McpModelsCommand extends McpRuntimeCommand
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
            'description' => 'Lists registered Kirby page models (runtime truth) with class + file path info (structured output for MCP).',
            'args' => [
                'idsOnly' => [
                    'longPrefix' => 'ids-only',
                    'description' => 'Return only model ids (minimal payload).',
                    'noValue' => true,
                ],
                'cursor' => [
                    'longPrefix' => 'cursor',
                    'description' => 'Pagination cursor (0-based offset in sorted filtered models). Default: 0.',
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

            $modelsRoot = $kirby->root('models');
            $modelsRoot = is_string($modelsRoot) ? rtrim($modelsRoot, DIRECTORY_SEPARATOR) : null;

            $pageModels = $kirby->extensions('pageModels');

            $ids = array_values(array_filter(array_keys($pageModels), static fn ($id) => is_string($id) && $id !== ''));
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

            $models = [];
            foreach ($pagedIds as $id) {
                if ($idsOnly === true) {
                    $models[] = ['id' => $id];
                    continue;
                }

                $class = $pageModels[$id] ?? null;
                $class = is_string($class) && trim($class) !== '' ? trim($class) : null;

                $classFile = null;
                if (is_string($class) && class_exists($class) === true) {
                    $file = (new ReflectionClass($class))->getFileName();
                    $classFile = is_string($file) && $file !== '' ? $file : null;
                }

                $activeSource = null;
                $relativeToModelsRoot = null;
                if (is_string($classFile) && is_string($modelsRoot) && $modelsRoot !== '' && str_starts_with($classFile, $modelsRoot . DIRECTORY_SEPARATOR)) {
                    $activeSource = 'file';
                    $relativeToModelsRoot = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', substr($classFile, strlen($modelsRoot))), '/');
                } elseif ($class !== null) {
                    $activeSource = 'extension';
                }

                $models[] = [
                    'id' => $id,
                    'name' => $id,
                    'class' => $class,
                    'activeSource' => $activeSource,
                    'sources' => $activeSource !== null ? [$activeSource] : [],
                    'overriddenByFile' => false,
                    'file' => [
                        'active' => is_string($classFile) ? [
                            'absolutePath' => $classFile,
                        ] : null,
                        'modelsRoot' => $activeSource === 'file' && is_string($classFile) ? [
                            'absolutePath' => $classFile,
                            'relativeToModelsRoot' => $relativeToModelsRoot,
                        ] : null,
                        'extension' => $activeSource === 'extension' ? [
                            'kind' => 'class',
                            'absolutePath' => $classFile,
                        ] : null,
                    ],
                ];
            }

            self::emit($cli, [
                'ok' => true,
                'idsOnly' => $idsOnly,
                'modelsRoot' => $modelsRoot,
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
                'models' => $models,
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
