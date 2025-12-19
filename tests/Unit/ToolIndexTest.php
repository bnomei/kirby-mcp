<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Docs\PanelReferenceIndex;
use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\ToolIndex;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;

it('indexes tools and resources via #[McpToolIndex]', function (): void {
    $projectRoot = dirname(__DIR__, 2);
    $toolsDir = $projectRoot . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Mcp' . DIRECTORY_SEPARATOR . 'Tools';
    $resourcesDir = $projectRoot . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Mcp' . DIRECTORY_SEPARATOR . 'Resources';
    $srcRoot = $projectRoot . DIRECTORY_SEPARATOR . 'src';

    expect(is_dir($toolsDir))->toBeTrue();
    expect(is_dir($resourcesDir))->toBeTrue();

    $ignoredNames = [
        'kirby://susie/{phase}/{step}',
    ];

    $expectedNames = [];
    $missingIndex = [];

    $scanDirs = [$toolsDir, $resourcesDir];

    foreach ($scanDirs as $scanDir) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($scanDir));
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
                $indexAttributes = $method->getAttributes(McpToolIndex::class);

                $mcpToolAttributes = $method->getAttributes(McpTool::class);
                if ($mcpToolAttributes !== []) {
                    /** @var McpTool $mcpTool */
                    $mcpTool = $mcpToolAttributes[0]->newInstance();
                    $expectedNames[] = $mcpTool->name;

                    if ($indexAttributes === []) {
                        $missingIndex[] = $class . '::' . $method->getName() . ' (' . $mcpTool->name . ')';
                    }

                    continue;
                }

                $resourceAttributes = $method->getAttributes(McpResource::class);
                if ($resourceAttributes !== []) {
                    /** @var McpResource $resource */
                    $resource = $resourceAttributes[0]->newInstance();
                    $expectedNames[] = $resource->uri;

                    if (!in_array($resource->uri, $ignoredNames, true) && $indexAttributes === []) {
                        $missingIndex[] = $class . '::' . $method->getName() . ' (' . $resource->uri . ')';
                    }

                    continue;
                }

                $templateAttributes = $method->getAttributes(McpResourceTemplate::class);
                if ($templateAttributes !== []) {
                    /** @var McpResourceTemplate $template */
                    $template = $templateAttributes[0]->newInstance();
                    $expectedNames[] = $template->uriTemplate;

                    if (!in_array($template->uriTemplate, $ignoredNames, true) && $indexAttributes === []) {
                        $missingIndex[] = $class . '::' . $method->getName() . ' (' . $template->uriTemplate . ')';
                    }
                }
            }
        }
    }

    $expectedNames = array_values(array_unique(array_diff($expectedNames, $ignoredNames)));
    sort($expectedNames);

    expect($missingIndex)->toBeEmpty();

    $indexedToolNames = array_map(
        static fn (array $row): string => $row['name'],
        ToolIndex::all(),
    );
    $indexedToolNames = array_values(array_unique($indexedToolNames));
    sort($indexedToolNames);

    $missingFromIndex = array_values(array_diff($expectedNames, $indexedToolNames));
    expect($missingFromIndex)->toBeEmpty();

    $extraInIndex = array_values(array_diff($indexedToolNames, $expectedNames));
    $allowedExtras = array_merge(
        array_map(
            static fn (string $type): string => 'kirby://section/' . $type,
            array_keys(PanelReferenceIndex::SECTION_TYPES),
        ),
        array_map(
            static fn (string $type): string => 'kirby://field/' . $type,
            array_keys(PanelReferenceIndex::FIELD_TYPES),
        ),
        array_map(
            static fn (string $type): string => 'kirby://field/' . $type . '/update-schema',
            array_keys(PanelReferenceIndex::FIELD_TYPES),
        ),
    );

    $unexpectedExtras = array_values(array_diff($extraInIndex, $allowedExtras));
    expect($unexpectedExtras)->toBeEmpty();

    foreach (ToolIndex::all() as $tool) {
        expect($tool['kind'])->toBeString()->not()->toBe('');
        expect($tool['name'])->toBeString()->not()->toBe('');
        expect($tool['title'])->toBeString()->not()->toBe('');
        expect($tool['whenToUse'])->toBeString()->not()->toBe('');
        expect($tool['keywords'])->toBeArray()->not()->toBeEmpty();
    }
});
