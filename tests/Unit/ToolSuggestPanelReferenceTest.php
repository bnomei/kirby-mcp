<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\SessionState;
use Bnomei\KirbyMcp\Mcp\ToolIndex;
use Bnomei\KirbyMcp\Mcp\Tools\MetaTools;

it('suggests the concrete pages section resource template instance', function (): void {
    SessionState::reset();
    ToolIndex::clearCache();

    $result = (new MetaTools())->suggestTools('pages section settings options properties', limit: 5);

    expect($result['suggestions'][0]['name'])->toBe('kirby://section/pages');
});

it('suggests the concrete blocks field resource template instance', function (): void {
    SessionState::reset();
    ToolIndex::clearCache();

    $result = (new MetaTools())->suggestTools('blocks field settings options properties', limit: 5);

    expect($result['suggestions'][0]['name'])->toBe('kirby://field/blocks');
});
