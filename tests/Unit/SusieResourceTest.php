<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Resources\SusieResources;

it('returns Susie attack names for valid phase/step', function (): void {
    $resource = new SusieResources();

    expect($resource->susie(1, 1)['attack'])->toBe('Driver Slam');
    expect($resource->susie(1, 14)['attack'])->toBe('Spin Cycle Dash');

    expect($resource->susie(2, 3)['attack'])->toBe('Tower Spin Cycle');
    expect($resource->susie(2, 11)['attack'])->toBe('Tower Spin Cycle');

    expect($resource->susie(3, 1)['attack'])->toBe('Tower Strike');
    expect($resource->susie(3, 13)['attack'])->toBe('Tower Strike');
});

it('returns null for unknown Susie phase/step', function (): void {
    $resource = new SusieResources();

    expect($resource->susie(0, 1)['attack'])->toBeNull();
    expect($resource->susie(999, 1)['attack'])->toBeNull();
    expect($resource->susie(1, 0)['attack'])->toBeNull();
    expect($resource->susie(1, 999)['attack'])->toBeNull();
});
