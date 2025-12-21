<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Resources\ToolExamplesResources;

it('lists curated MCP tool examples', function (): void {
    $resource = new ToolExamplesResources();
    $examples = $resource->toolExamples();

    expect($examples)->toBeArray()->not()->toBeEmpty();

    $tools = array_map(static fn (array $entry): string => $entry['tool'] ?? '', $examples);
    expect($tools)->toContain('kirby_update_page_content');
    expect($tools)->toContain('kirby_run_cli_command');
});
