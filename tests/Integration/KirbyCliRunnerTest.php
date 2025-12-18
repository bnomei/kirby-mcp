<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;

it('runs the Kirby CLI against the cms fixture', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);

    $result = new KirbyCliRunner()->run(projectRoot: cmsPath(), args: ['version'], timeoutSeconds: 30);

    expect($result->exitCode)->toBe(0);
    expect($result->timedOut)->toBeFalse();
    expect($result->stdout)->toMatch('/\\b\\d+\\.\\d+\\.\\d+\\b/');
});
