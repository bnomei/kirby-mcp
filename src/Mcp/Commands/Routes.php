<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Commands;

use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommand;
use Closure;
use Kirby\CLI\CLI;
use Kirby\Plugin\Plugin;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;

final class Routes extends RuntimeCommand
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
            'description' => 'Lists registered Kirby routes (runtime truth) with pattern/method and best-effort source location (structured output for MCP).',
            'args' => [
                'patternsOnly' => [
                    'longPrefix' => 'patterns-only',
                    'description' => 'Return only pattern/method/name (minimal payload; omits action/source details).',
                    'noValue' => true,
                ],
                'method' => [
                    'longPrefix' => 'method',
                    'description' => 'Filter by HTTP method (GET, POST, ...).',
                ],
                'patternContains' => [
                    'longPrefix' => 'pattern-contains',
                    'description' => 'Filter routes whose pattern contains this substring (case-insensitive).',
                ],
                'cursor' => [
                    'longPrefix' => 'cursor',
                    'description' => 'Pagination cursor (0-based offset in filtered routes). Default: 0.',
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
            $patternsOnly = $cli->arg('patternsOnly') === true;

            $methodFilter = $cli->arg('method');
            $methodFilter = is_string($methodFilter) && trim($methodFilter) !== '' ? strtoupper(trim($methodFilter)) : null;

            $patternContains = $cli->arg('patternContains');
            $patternContains = is_string($patternContains) && trim($patternContains) !== '' ? trim($patternContains) : null;

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

            $projectRoot = rtrim($cli->dir(), DIRECTORY_SEPARATOR);

            $configRoot = $kirby->root('config');
            $configRoot = is_string($configRoot) && $configRoot !== '' ? rtrim($configRoot, DIRECTORY_SEPARATOR) : null;

            $pluginRootIndex = self::pluginRootIndex($kirby->plugins());

            $routes = $kirby->routes();

            $filtered = [];
            $errors = [];

            $routeIndex = 0;
            foreach ($routes as $route) {
                $routeIndex++;

                if (!is_array($route)) {
                    $errors[] = [
                        'index' => $routeIndex - 1,
                        'error' => 'Route entry is not an array',
                        'type' => gettype($route),
                    ];
                    continue;
                }

                $pattern = $route['pattern'] ?? null;
                $method = $route['method'] ?? null;
                $method = is_string($method) && trim($method) !== '' ? strtoupper(trim($method)) : 'GET';

                if (is_string($methodFilter) && $methodFilter !== '') {
                    if (self::methodMatchesFilter($method, $methodFilter) !== true) {
                        continue;
                    }
                }

                if (is_string($patternContains) && $patternContains !== '') {
                    if (self::patternMatchesFilter($pattern, $patternContains) !== true) {
                        continue;
                    }
                }

                $filtered[] = self::normalizeRoute(
                    route: $route,
                    index: $routeIndex - 1,
                    projectRoot: $projectRoot,
                    configRoot: $configRoot,
                    pluginRootIndex: $pluginRootIndex,
                    patternsOnly: $patternsOnly,
                );
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

            self::emit($cli, [
                'ok' => true,
                'patternsOnly' => $patternsOnly,
                'filters' => [
                    'method' => $methodFilter,
                    'patternContains' => $patternContains,
                ],
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
                    'errors' => count($errors),
                ],
                'routes' => $paged,
                'errors' => $errors,
            ]);
        } catch (Throwable $exception) {
            self::emit($cli, [
                'ok' => false,
                'error' => self::errorArray($exception, self::traceForCli($cli, $exception)),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $route
     * @param array<int, array{id: string, root: string}> $pluginRootIndex
     * @return array<string, mixed>
     */
    private static function normalizeRoute(
        array $route,
        int $index,
        string $projectRoot,
        ?string $configRoot,
        array $pluginRootIndex,
        bool $patternsOnly,
    ): array {
        $pattern = $route['pattern'] ?? null;
        $method = $route['method'] ?? null;
        $method = is_string($method) && trim($method) !== '' ? strtoupper(trim($method)) : 'GET';

        $name = $route['name'] ?? null;
        $name = is_string($name) && trim($name) !== '' ? trim($name) : null;

        $entry = [
            'index' => $index,
            'pattern' => $pattern,
            'method' => $method,
            'name' => $name,
        ];

        if ($patternsOnly === true) {
            return $entry;
        }

        $action = $route['action'] ?? null;
        $actionInfo = self::callableInfo($action);

        $source = self::sourceInfo(
            projectRoot: $projectRoot,
            configRoot: $configRoot,
            pluginRootIndex: $pluginRootIndex,
            file: $actionInfo['file'] ?? null,
        );

        $entry['action'] = $actionInfo;
        $entry['source'] = $source;

        return $entry;
    }

    private static function methodMatchesFilter(string $routeMethod, string $filter): bool
    {
        $routeMethod = strtoupper(trim($routeMethod));
        $filter = strtoupper(trim($filter));

        if ($filter === '') {
            return true;
        }

        if ($routeMethod === '') {
            $routeMethod = 'GET';
        }

        $parts = array_map('trim', explode('|', $routeMethod));
        $parts = array_filter($parts, static fn (string $part): bool => $part !== '');
        if ($parts === []) {
            $parts = ['GET'];
        }

        return in_array($filter, $parts, true);
    }

    private static function patternMatchesFilter(mixed $pattern, string $needle): bool
    {
        $needle = trim($needle);
        if ($needle === '') {
            return true;
        }

        if (is_string($pattern)) {
            return stripos($pattern, $needle) !== false;
        }

        if (is_array($pattern)) {
            foreach ($pattern as $value) {
                if (is_string($value) && stripos($value, $needle) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $plugins
     * @return array<int, array{id: string, root: string}>
     */
    private static function pluginRootIndex(array $plugins): array
    {
        $index = [];

        foreach ($plugins as $id => $plugin) {
            if (!is_string($id) || $id === '') {
                continue;
            }
            if (!$plugin instanceof Plugin) {
                continue;
            }

            $root = $plugin->root();
            if (!is_string($root) || $root === '') {
                continue;
            }

            $index[] = [
                'id' => $id,
                'root' => rtrim($root, DIRECTORY_SEPARATOR),
            ];
        }

        usort($index, static function (array $a, array $b): int {
            $cmp = strlen($b['root']) <=> strlen($a['root']);
            return $cmp !== 0 ? $cmp : strcmp($a['id'], $b['id']);
        });

        return $index;
    }

    /**
     * @return array<string, mixed>
     */
    private static function callableInfo(mixed $callable): array
    {
        if ($callable instanceof Closure) {
            $reflection = new ReflectionFunction($callable);

            $file = $reflection->getFileName();
            $file = is_string($file) && $file !== '' ? $file : null;

            $startLine = $reflection->getStartLine();
            $startLine = is_int($startLine) && $startLine > 0 ? $startLine : null;

            $endLine = $reflection->getEndLine();
            $endLine = is_int($endLine) && $endLine > 0 ? $endLine : null;

            return [
                'kind' => 'closure',
                'callable' => $reflection->getName(),
                'class' => null,
                'method' => null,
                'function' => $reflection->getName(),
                'file' => $file,
                'startLine' => $startLine,
                'endLine' => $endLine,
            ];
        }

        if (is_array($callable) && count($callable) === 2) {
            $classOrObject = $callable[0] ?? null;
            $method = $callable[1] ?? null;

            if ((is_object($classOrObject) || is_string($classOrObject)) && is_string($method) && $method !== '') {
                $reflection = new ReflectionMethod($classOrObject, $method);

                $file = $reflection->getFileName();
                $file = is_string($file) && $file !== '' ? $file : null;

                $startLine = $reflection->getStartLine();
                $startLine = is_int($startLine) && $startLine > 0 ? $startLine : null;

                $endLine = $reflection->getEndLine();
                $endLine = is_int($endLine) && $endLine > 0 ? $endLine : null;

                $class = $reflection->getDeclaringClass()->getName();

                return [
                    'kind' => 'method',
                    'callable' => $class . '::' . $reflection->getName(),
                    'class' => $class,
                    'method' => $reflection->getName(),
                    'function' => null,
                    'file' => $file,
                    'startLine' => $startLine,
                    'endLine' => $endLine,
                ];
            }
        }

        if (is_string($callable) && trim($callable) !== '') {
            $callable = trim($callable);

            if (str_contains($callable, '::')) {
                [$class, $method] = explode('::', $callable, 2);
                $class = trim($class);
                $method = trim($method);

                if ($class !== '' && $method !== '' && class_exists($class) && method_exists($class, $method)) {
                    $reflection = new ReflectionMethod($class, $method);

                    $file = $reflection->getFileName();
                    $file = is_string($file) && $file !== '' ? $file : null;

                    $startLine = $reflection->getStartLine();
                    $startLine = is_int($startLine) && $startLine > 0 ? $startLine : null;

                    $endLine = $reflection->getEndLine();
                    $endLine = is_int($endLine) && $endLine > 0 ? $endLine : null;

                    $class = $reflection->getDeclaringClass()->getName();

                    return [
                        'kind' => 'method',
                        'callable' => $class . '::' . $reflection->getName(),
                        'class' => $class,
                        'method' => $reflection->getName(),
                        'function' => null,
                        'file' => $file,
                        'startLine' => $startLine,
                        'endLine' => $endLine,
                    ];
                }

                return [
                    'kind' => 'callable_string',
                    'callable' => $callable,
                    'class' => $class !== '' ? $class : null,
                    'method' => $method !== '' ? $method : null,
                    'function' => null,
                    'file' => null,
                    'startLine' => null,
                    'endLine' => null,
                ];
            }

            if (function_exists($callable)) {
                $reflection = new ReflectionFunction($callable);

                $file = $reflection->getFileName();
                $file = is_string($file) && $file !== '' ? $file : null;

                $startLine = $reflection->getStartLine();
                $startLine = is_int($startLine) && $startLine > 0 ? $startLine : null;

                $endLine = $reflection->getEndLine();
                $endLine = is_int($endLine) && $endLine > 0 ? $endLine : null;

                return [
                    'kind' => 'function',
                    'callable' => $reflection->getName(),
                    'class' => null,
                    'method' => null,
                    'function' => $reflection->getName(),
                    'file' => $file,
                    'startLine' => $startLine,
                    'endLine' => $endLine,
                ];
            }

            return [
                'kind' => 'callable_string',
                'callable' => $callable,
                'class' => null,
                'method' => null,
                'function' => null,
                'file' => null,
                'startLine' => null,
                'endLine' => null,
            ];
        }

        if (is_object($callable) && is_callable($callable)) {
            $class = $callable::class;
            if (method_exists($callable, '__invoke')) {
                $reflection = new ReflectionMethod($callable, '__invoke');

                $file = $reflection->getFileName();
                $file = is_string($file) && $file !== '' ? $file : null;

                $startLine = $reflection->getStartLine();
                $startLine = is_int($startLine) && $startLine > 0 ? $startLine : null;

                $endLine = $reflection->getEndLine();
                $endLine = is_int($endLine) && $endLine > 0 ? $endLine : null;

                return [
                    'kind' => 'invokable',
                    'callable' => $class . '::__invoke',
                    'class' => $class,
                    'method' => '__invoke',
                    'function' => null,
                    'file' => $file,
                    'startLine' => $startLine,
                    'endLine' => $endLine,
                ];
            }

            return [
                'kind' => 'invokable',
                'callable' => $class,
                'class' => $class,
                'method' => null,
                'function' => null,
                'file' => null,
                'startLine' => null,
                'endLine' => null,
            ];
        }

        return [
            'kind' => 'unknown',
            'callable' => null,
            'class' => null,
            'method' => null,
            'function' => null,
            'file' => null,
            'startLine' => null,
            'endLine' => null,
        ];
    }

    /**
     * @param array<int, array{id: string, root: string}> $pluginRootIndex
     * @return array<string, mixed>
     */
    private static function sourceInfo(
        string $projectRoot,
        ?string $configRoot,
        array $pluginRootIndex,
        ?string $file,
    ): array {
        if (!is_string($file) || $file === '') {
            return [
                'kind' => 'unknown',
                'pluginId' => null,
                'absolutePath' => null,
                'relativePath' => null,
            ];
        }

        $pluginId = self::pluginIdForFile($pluginRootIndex, $file);
        $kind = null;

        if (is_string($pluginId) && $pluginId !== '') {
            $kind = 'plugin';
        } elseif (is_string($configRoot) && $configRoot !== '' && str_starts_with($file, $configRoot . DIRECTORY_SEPARATOR)) {
            $kind = 'config';
        } elseif (str_contains($file, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'getkirby' . DIRECTORY_SEPARATOR)) {
            $kind = 'core';
        } elseif (str_contains($file, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
            $kind = 'vendor';
        } elseif (str_starts_with($file, rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
            $kind = 'project';
        } else {
            $kind = 'unknown';
        }

        $relativePath = str_starts_with($file, rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)
            ? ltrim(substr($file, strlen(rtrim($projectRoot, DIRECTORY_SEPARATOR))), DIRECTORY_SEPARATOR)
            : $file;

        return [
            'kind' => $kind,
            'pluginId' => $pluginId,
            'absolutePath' => $file,
            'relativePath' => $relativePath,
        ];
    }

    /**
     * @param array<int, array{id: string, root: string}> $pluginRootIndex
     */
    private static function pluginIdForFile(array $pluginRootIndex, string $file): ?string
    {
        foreach ($pluginRootIndex as $plugin) {
            $root = $plugin['root'] ?? null;
            $id = $plugin['id'] ?? null;

            if (!is_string($root) || $root === '' || !is_string($id) || $id === '') {
                continue;
            }

            if ($file === $root) {
                return $id;
            }

            if (str_starts_with($file, $root . DIRECTORY_SEPARATOR)) {
                return $id;
            }
        }

        return null;
    }
}
