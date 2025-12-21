<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Resources\MetaResources;

it('exposes the tool index as a resource', function (): void {
    $resource = new MetaResources();
    $index = $resource->toolIndex();

    expect($index)->toBeArray()->not()->toBeEmpty();

    $names = array_map(
        static fn (array $row): string => $row['name'] ?? '',
        $index,
    );

    expect($names)->toContain('kirby_tool_suggest');
});
