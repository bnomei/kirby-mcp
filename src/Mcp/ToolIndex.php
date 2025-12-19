<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Bnomei\KirbyMcp\Docs\PanelReferenceIndex;
use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;

/**
 * Canonical MCP index (keyword weights + usage hints).
 *
 * This is built via reflection from tools/resources/resource templates that opt-in with #[McpToolIndex].
 */
final class ToolIndex
{
    /** @var array<int, array{kind:string, name:string, title:string, whenToUse:string, keywords:array<string,int>}>|null */
    private static ?array $cache = null;

    public static function clearCache(): void
    {
        self::$cache = null;
    }

    /**
     * @return array<int, array{
     *   kind: 'tool'|'resource'|'resource_template',
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

        $items = [];

        $srcRoot = dirname(__DIR__);

        $scanDirs = [
            __DIR__ . DIRECTORY_SEPARATOR . 'Tools',
            __DIR__ . DIRECTORY_SEPARATOR . 'Resources',
        ];

        foreach ($scanDirs as $directory) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
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
                    $indexAttributes = $method->getAttributes(McpToolIndex::class);
                    if ($indexAttributes === []) {
                        continue;
                    }

                    /** @var McpToolIndex $index */
                    $index = $indexAttributes[0]->newInstance();

                    $mcpToolAttributes = $method->getAttributes(McpTool::class);
                    if ($mcpToolAttributes !== []) {
                        /** @var McpTool $mcpTool */
                        $mcpTool = $mcpToolAttributes[0]->newInstance();

                        $name = is_string($mcpTool->name) ? trim($mcpTool->name) : '';
                        if ($name === '') {
                            continue;
                        }

                        $title = is_object($mcpTool->annotations) && is_string($mcpTool->annotations->title) && $mcpTool->annotations->title !== ''
                            ? $mcpTool->annotations->title
                            : $name;

                        $items[$name] = [
                            'kind' => 'tool',
                            'name' => $name,
                            'title' => $title,
                            'whenToUse' => $index->whenToUse,
                            'keywords' => $index->keywords,
                        ];
                        continue;
                    }

                    $mcpResourceAttributes = $method->getAttributes(McpResource::class);
                    if ($mcpResourceAttributes !== []) {
                        /** @var McpResource $resource */
                        $resource = $mcpResourceAttributes[0]->newInstance();
                        $uri = trim($resource->uri);
                        if ($uri === '') {
                            continue;
                        }

                        $items[$uri] = [
                            'kind' => 'resource',
                            'name' => $uri,
                            'title' => $uri,
                            'whenToUse' => $index->whenToUse,
                            'keywords' => $index->keywords,
                        ];
                        continue;
                    }

                    $mcpTemplateAttributes = $method->getAttributes(McpResourceTemplate::class);
                    if ($mcpTemplateAttributes !== []) {
                        /** @var McpResourceTemplate $template */
                        $template = $mcpTemplateAttributes[0]->newInstance();
                        $uriTemplate = trim($template->uriTemplate);
                        if ($uriTemplate === '') {
                            continue;
                        }

                        $items[$uriTemplate] = [
                            'kind' => 'resource_template',
                            'name' => $uriTemplate,
                            'title' => $uriTemplate,
                            'whenToUse' => $index->whenToUse,
                            'keywords' => $index->keywords,
                        ];

                        self::expandPanelReferenceTemplateInstances(
                            items: $items,
                            uriTemplate: $uriTemplate,
                            whenToUse: $index->whenToUse,
                            baseKeywords: $index->keywords,
                        );
                    }
                }
            }
        }

        ksort($items);
        self::$cache = array_values($items);

        return self::$cache;
    }

    /**
     * Add curated "instance" entries for common Kirby resources/templates so the "obvious path" shows up directly in suggestions
     * (e.g. `kirby://section/pages` instead of only `kirby://section/{type}`).
     *
     * @param array<string, array{kind:string, name:string, title:string, whenToUse:string, keywords:array<string,int>}> $items
     * @param array<string, int> $baseKeywords
     */
    private static function expandPanelReferenceTemplateInstances(array &$items, string $uriTemplate, string $whenToUse, array $baseKeywords): void
    {
        if ($uriTemplate === 'kirby://section/{type}') {
            foreach (PanelReferenceIndex::SECTION_TYPES as $type => $label) {
                $uri = 'kirby://section/' . $type;

                if (isset($items[$uri])) {
                    continue;
                }

                $keywords = $baseKeywords;
                $keywords[$type] = max($keywords[$type] ?? 0, 120);

                $items[$uri] = [
                    'kind' => 'resource_template',
                    'name' => $uri,
                    'title' => $label,
                    'whenToUse' => $whenToUse,
                    'keywords' => $keywords,
                ];
            }
        }

        if ($uriTemplate === 'kirby://field/{type}') {
            foreach (PanelReferenceIndex::FIELD_TYPES as $type => $label) {
                $uri = 'kirby://field/' . $type;

                if (isset($items[$uri])) {
                    continue;
                }

                $keywords = $baseKeywords;
                $keywords[$type] = max($keywords[$type] ?? 0, 120);

                $items[$uri] = [
                    'kind' => 'resource_template',
                    'name' => $uri,
                    'title' => $label,
                    'whenToUse' => $whenToUse,
                    'keywords' => $keywords,
                ];
            }
        }

        if ($uriTemplate === 'kirby://field/{type}/update-schema') {
            foreach (PanelReferenceIndex::FIELD_TYPES as $type => $label) {
                $uri = 'kirby://field/' . $type . '/update-schema';

                if (isset($items[$uri])) {
                    continue;
                }

                $keywords = $baseKeywords;
                $keywords[$type] = max($keywords[$type] ?? 0, 120);

                $items[$uri] = [
                    'kind' => 'resource_template',
                    'name' => $uri,
                    'title' => $label . ' update schema',
                    'whenToUse' => $whenToUse,
                    'keywords' => $keywords,
                ];
            }
        }
    }
}
