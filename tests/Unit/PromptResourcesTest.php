<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Resources\PromptResources;
use Mcp\Exception\ResourceReadException;

it('lists MCP prompts as a resource', function (): void {
    $resource = new PromptResources();
    $prompts = $resource->prompts();

    expect($prompts)->toBeArray()->not()->toBeEmpty();

    $names = array_map(
        static fn (array $row): string => $row['name'],
        $prompts,
    );

    expect($names)->toContain('kirby_project_tour');
    expect($names)->toContain('kirby_performance_audit');
});

it('returns prompt details and renders default messages', function (): void {
    $resource = new PromptResources();
    $prompt = $resource->prompt('kirby_project_tour');

    expect($prompt['name'])->toBe('kirby_project_tour');
    expect($prompt['messages'])->toBeArray()->not()->toBeEmpty();
    expect($prompt['renderError'])->toBeNull();

    $first = $prompt['messages'][0] ?? null;
    expect($first)->toBeArray();
    expect($first['role'])->toBeString()->not()->toBe('');
    expect($first['content'])->toBeString()->not()->toBe('');
});

it('validates prompt names', function (): void {
    $resource = new PromptResources();

    expect(fn () => $resource->prompt(''))->toThrow(ResourceReadException::class);
    expect(fn () => $resource->prompt('nope'))->toThrow(ResourceReadException::class);
});
