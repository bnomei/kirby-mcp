<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;

it('runs the Kirby CLI against the cms fixture', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);

    $runner = new KirbyCliRunner();
    $result = $runner->run(projectRoot: cmsPath(), args: ['version'], timeoutSeconds: 30);

    expect($result->exitCode)->toBe(0);
    expect($result->timedOut)->toBeFalse();
    expect($result->stdout)->toMatch('/\\b\\d+\\.\\d+\\.\\d+\\b/');
});

it('uses KIRBY_MCP_PHP_BINARY when configured', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);

    // Prove the env var is respected: an invalid binary must cause failure.
    putenv('KIRBY_MCP_PHP_BINARY=/nonexistent/php');
    try {
        $result = (new KirbyCliRunner())->run(projectRoot: cmsPath(), args: ['version'], timeoutSeconds: 30);

        expect($result->exitCode)->not()->toBe(0);
    } finally {
        putenv('KIRBY_MCP_PHP_BINARY');
    }

    // Now verify it works when set to a real binary.
    putenv('KIRBY_MCP_PHP_BINARY=' . PHP_BINARY);
    try {
        $result = (new KirbyCliRunner())->run(projectRoot: cmsPath(), args: ['version'], timeoutSeconds: 30);
        expect($result->exitCode)->toBe(0);
        expect($result->timedOut)->toBeFalse();
        expect($result->stdout)->toMatch('/\\b\\d+\\.\\d+\\.\\d+\\b/');
    } finally {
        putenv('KIRBY_MCP_PHP_BINARY');
    }
});
