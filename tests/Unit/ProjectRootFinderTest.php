<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Project\ProjectRootFinder;

it('detects the Kirby project root from a nested directory', function (): void {
    $finder = new ProjectRootFinder();

    $root = $finder->findKirbyProjectRoot(cmsPath() . '/site/templates');

    expect($root)->toBe(cmsPath());
});

it('returns null when no Kirby composer project is found', function (): void {
    $finder = new ProjectRootFinder();

    $root = $finder->findKirbyProjectRoot(__DIR__);

    expect($root)->toBeNull();
});
