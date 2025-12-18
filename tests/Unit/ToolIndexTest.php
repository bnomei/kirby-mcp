<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\ToolIndex;
use Mcp\Capability\Attribute\McpTool;

it('indexes every MCP tool via #[McpToolIndex]', function (): void {
    $projectRoot = dirname(__DIR__, 2);
    $toolsDir = $projectRoot . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Mcp' . DIRECTORY_SEPARATOR . 'Tools';
    $srcRoot = $projectRoot . DIRECTORY_SEPARATOR . 'src';

    expect(is_dir($toolsDir))->toBeTrue();

    $expectedToolNames = [];
    $missingIndex = [];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($toolsDir));
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

        $reflection = new ReflectionClass($class);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $mcpToolAttributes = $method->getAttributes(McpTool::class);
            if ($mcpToolAttributes === []) {
                continue;
            }

            /** @var McpTool $mcpTool */
            $mcpTool = $mcpToolAttributes[0]->newInstance();
            $expectedToolNames[] = $mcpTool->name;

            $indexAttributes = $method->getAttributes(McpToolIndex::class);
            if ($indexAttributes === []) {
                $missingIndex[] = $class . '::' . $method->getName() . ' (' . $mcpTool->name . ')';
            }
        }
    }

    $expectedToolNames = array_values(array_unique($expectedToolNames));
    sort($expectedToolNames);

    expect($missingIndex)->toBeEmpty();

    $indexedToolNames = array_map(
        static fn (array $row): string => $row['name'],
        ToolIndex::all(),
    );
    $indexedToolNames = array_values(array_unique($indexedToolNames));
    sort($indexedToolNames);

    expect($indexedToolNames)->toBe($expectedToolNames);

    foreach (ToolIndex::all() as $tool) {
        expect($tool['name'])->toBeString()->not()->toBe('');
        expect($tool['title'])->toBeString()->not()->toBe('');
        expect($tool['whenToUse'])->toBeString()->not()->toBe('');
        expect($tool['keywords'])->toBeArray()->not()->toBeEmpty();
    }
});
