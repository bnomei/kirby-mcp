<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\SessionState;
use Bnomei\KirbyMcp\Mcp\Tools\MetaTools;

it('suggests the blueprint index tool for blueprint queries', function (): void {
    SessionState::reset();

    $data = (new MetaTools())->suggestTools(query: 'blueprint yaml');

    expect($data)->toHaveKey('suggestions');
    expect($data['suggestions'][0]['tool'])->toBe('kirby_blueprints_index');
    expect($data['initRecommended'])->toBeTrue();
});
