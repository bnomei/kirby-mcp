<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Project\EnvironmentDetector;

it('defaults to plain php for the cms fixture', function (): void {
    $info = (new EnvironmentDetector())->detect(cmsPath());

    expect($info->localRunner)->toBe('php');
});
