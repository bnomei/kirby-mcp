<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Project\ProjectInfoInspector;

it('inspects project info for the cms fixture', function (): void {
    $info = (new ProjectInfoInspector())->inspect(cmsPath());

    expect($info['projectRoot'])->toBe(cmsPath());
    expect($info['phpVersion'])->toBe(PHP_VERSION);
    expect($info['kirbyVersion'])->toBeString()->not()->toBe('');

    $environment = $info['environment'] ?? null;
    expect($environment)->toBeArray();
    expect($environment['projectRoot'])->toBe(cmsPath());
    expect($environment['localRunner'])->toBeString();
    expect($environment['signals'])->toBeArray();

    $composer = $info['composer'] ?? null;
    expect($composer)->toBeArray();
    expect($composer['projectRoot'])->toBe(cmsPath());
    expect($composer['composerJson']['require']['getkirby/cms'])->toBeString();
    expect($composer)->not()->toHaveKey('composerLock');
});
