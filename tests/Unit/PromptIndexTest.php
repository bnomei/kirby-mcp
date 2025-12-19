<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\PromptIndex;
use Mcp\Capability\Attribute\McpPrompt;

it('indexes every MCP prompt via #[McpPrompt]', function (): void {
    $projectRoot = dirname(__DIR__, 2);
    $promptsDir = $projectRoot . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Mcp' . DIRECTORY_SEPARATOR . 'Prompts';
    $srcRoot = $projectRoot . DIRECTORY_SEPARATOR . 'src';

    expect(is_dir($promptsDir))->toBeTrue();

    $expectedPromptNames = [];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($promptsDir));
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
            $promptAttributes = $method->getAttributes(McpPrompt::class);
            if ($promptAttributes === []) {
                continue;
            }

            /** @var McpPrompt $mcpPrompt */
            $mcpPrompt = $promptAttributes[0]->newInstance();
            $name = is_string($mcpPrompt->name) && trim($mcpPrompt->name) !== '' ? trim($mcpPrompt->name) : $method->getName();
            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $expectedPromptNames[] = $name;
        }
    }

    $expectedPromptNames = array_values(array_unique($expectedPromptNames));
    sort($expectedPromptNames);

    $indexedPromptNames = array_map(
        static fn (array $row): string => $row['name'],
        PromptIndex::all(),
    );
    $indexedPromptNames = array_values(array_unique($indexedPromptNames));
    sort($indexedPromptNames);

    expect($indexedPromptNames)->toBe($expectedPromptNames);

    foreach (PromptIndex::all() as $prompt) {
        expect($prompt['name'])->toBeString()->not()->toBe('');
        expect($prompt['description'])->toBeString()->not()->toBe('');
        expect($prompt['args'])->toBeArray();
        expect($prompt['generator'])->toBeArray();
        expect($prompt['generator']['class'])->toBeString()->not()->toBe('');
        expect($prompt['generator']['method'])->toBeString()->not()->toBe('');
        expect($prompt['resource'])->toBe('kirby://prompt/' . rawurlencode($prompt['name']));

        foreach ($prompt['args'] as $arg) {
            expect($arg)->toBeArray();
            expect($arg['name'])->toBeString()->not()->toBe('');
            expect($arg['type'])->toBeString()->not()->toBe('');
            expect($arg['required'])->toBeBool();
            expect(array_key_exists('default', $arg))->toBeTrue();
            expect(array_key_exists('completion', $arg))->toBeTrue();
        }
    }
});
