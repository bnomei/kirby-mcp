<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Mcp\Capability\Attribute\McpTool;

/**
 * Canonical tool index (keyword weights + usage hints).
 *
 * This is built via reflection from tool methods that opt-in with #[McpToolIndex].
 */
final class ToolIndex
{
    /** @var array<int, array{name:string, title:string, whenToUse:string, keywords:array<string,int>}>|null */
    private static ?array $cache = null;

    /**
     * @return array<int, array{
     *   name: string,
     *   title: string,
     *   whenToUse: string,
     *   keywords: array<string, int>
     * }>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $tools = [];

        $toolsDir = __DIR__ . DIRECTORY_SEPARATOR . 'Tools';
        $srcRoot = dirname(__DIR__);

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($toolsDir));
        foreach ($iterator as $file) {
            if ($file->isFile() === false || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            $relative = ltrim(substr($path, strlen($srcRoot)), DIRECTORY_SEPARATOR);
            $relative = preg_replace('/\\.php$/i', '', $relative) ?? $relative;
            $class = 'Bnomei\\KirbyMcp\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new \ReflectionClass($class);
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $mcpToolAttributes = $method->getAttributes(McpTool::class);
                if ($mcpToolAttributes === []) {
                    continue;
                }

                $indexAttributes = $method->getAttributes(McpToolIndex::class);
                if ($indexAttributes === []) {
                    continue;
                }

                /** @var McpTool $mcpTool */
                $mcpTool = $mcpToolAttributes[0]->newInstance();
                /** @var McpToolIndex $index */
                $index = $indexAttributes[0]->newInstance();

                $name = is_string($mcpTool->name) ? trim($mcpTool->name) : '';
                if ($name === '') {
                    continue;
                }

                $title = is_object($mcpTool->annotations) && is_string($mcpTool->annotations->title) && $mcpTool->annotations->title !== ''
                    ? $mcpTool->annotations->title
                    : $name;

                $tools[$name] = [
                    'name' => $name,
                    'title' => $title,
                    'whenToUse' => $index->whenToUse,
                    'keywords' => $index->keywords,
                ];
            }
        }

        ksort($tools);
        self::$cache = array_values($tools);

        return self::$cache;
    }
}
