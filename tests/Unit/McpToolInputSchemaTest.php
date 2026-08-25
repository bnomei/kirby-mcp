<?php

declare(strict_types=1);

use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Discovery\DocBlockParser;
use Mcp\Capability\Discovery\SchemaGenerator;

it('exposes strict-compatible input schemas for all MCP tools', function (): void {
    $generator = new SchemaGenerator(new DocBlockParser());
    $violations = [];

    $toolsDir = dirname(__DIR__, 2) . '/src/Mcp/Tools';
    foreach (glob($toolsDir . '/*.php') ?: [] as $file) {
        $class = 'Bnomei\\KirbyMcp\\Mcp\\Tools\\' . basename($file, '.php');
        if (!class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $toolAttributes = $method->getAttributes(McpTool::class);
            if ($toolAttributes === []) {
                continue;
            }

            /** @var McpTool $tool */
            $tool = $toolAttributes[0]->newInstance();
            $schema = $generator->generate($method);

            assertNoArraySchemaWithoutItems(
                $schema,
                '#',
                is_string($tool->name) && $tool->name !== '' ? $tool->name : $method->getName(),
                $violations,
            );
        }
    }

    expect($violations)->toBe([]);
});

it('describes known list parameters as string arrays', function (): void {
    $schemas = generatedToolInputSchemas();

    expect($schemas['kirby_tool_suggest']['properties']['keywords'])
        ->toMatchArray(['type' => 'array', 'items' => ['type' => 'string']]);
    expect($schemas['kirby_run_cli_command']['properties']['arguments'])
        ->toMatchArray(['type' => 'array', 'items' => ['type' => 'string']]);

    foreach ([
        'kirby_blueprints_index',
        'kirby_templates_index',
        'kirby_snippets_index',
        'kirby_collections_index',
        'kirby_controllers_index',
        'kirby_models_index',
        'kirby_plugins_index',
    ] as $toolName) {
        $fields = $schemas[$toolName]['properties']['fields'] ?? null;

        expect($fields)->toBeArray();
        expect($fields['type'] ?? null)->toContain('array');
        expect($fields['type'] ?? null)->toContain('null');
        expect($fields['items'] ?? null)->toBe(['type' => 'string']);
    }
});

it('keeps update content data modeled as object or JSON string', function (): void {
    $schemas = generatedToolInputSchemas();

    foreach ([
        'kirby_update_page_content',
        'kirby_update_site_content',
        'kirby_update_file_content',
        'kirby_update_user_content',
    ] as $toolName) {
        $data = $schemas[$toolName]['properties']['data'] ?? null;

        expect($data)->toBeArray();
        expect($data['type'] ?? null)->toBeArray();
        expect($data['type'])->toContain('object');
        expect($data['type'])->toContain('string');
        expect($data['type'])->not()->toContain('array');
    }
});

it('keeps empty tool inputs as objects and omits RequestContext from public schemas', function (): void {
    $schemas = generatedToolInputSchemas();
    $init = $schemas['kirby_init'];

    expect($init['type'] ?? null)->toBe('object');
    expect($init['properties'] ?? null)->toEqual(new stdClass());

    foreach ($schemas as $schema) {
        $properties = $schema['properties'] ?? [];
        expect($properties)->not()->toHaveKey('context');
    }
});

/**
 * @param array<string, mixed> $schema
 * @param array<int, string> $violations
 */
function assertNoArraySchemaWithoutItems(array $schema, string $path, string $toolName, array &$violations): void
{
    $type = $schema['type'] ?? null;
    $isArraySchema = $type === 'array' || (is_array($type) && in_array('array', $type, true));

    if ($isArraySchema && !array_key_exists('items', $schema)) {
        $violations[] = $toolName . ' ' . $path;
    }

    foreach ($schema as $key => $value) {
        if (!is_array($value)) {
            continue;
        }

        assertNoArraySchemaWithoutItems(
            $value,
            $path . '/' . (is_int($key) ? (string) $key : $key),
            $toolName,
            $violations,
        );
    }
}

/**
 * @return array<string, array<string, mixed>>
 */
function generatedToolInputSchemas(): array
{
    $generator = new SchemaGenerator(new DocBlockParser());
    $schemas = [];

    $toolsDir = dirname(__DIR__, 2) . '/src/Mcp/Tools';
    foreach (glob($toolsDir . '/*.php') ?: [] as $file) {
        $class = 'Bnomei\\KirbyMcp\\Mcp\\Tools\\' . basename($file, '.php');
        if (!class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $toolAttributes = $method->getAttributes(McpTool::class);
            if ($toolAttributes === []) {
                continue;
            }

            /** @var McpTool $tool */
            $tool = $toolAttributes[0]->newInstance();
            if (!is_string($tool->name) || $tool->name === '') {
                continue;
            }

            $schemas[$tool->name] = $generator->generate($method);
        }
    }

    return $schemas;
}
