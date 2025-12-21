<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Resources\ProjectResources;

it('returns project info via kirby://info resource', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    $originalRoot = getenv(ProjectContext::ENV_PROJECT_ROOT);
    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . cmsPath());
    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);

    try {
        $resource = new ProjectResources();
        $payload = $resource->projectInfo();

        expect($payload['projectRoot'])->toBe(cmsPath());
        expect($payload['phpVersion'])->toBeString()->not()->toBe('');
        expect($payload['kirbyVersion'])->toBeString()->not()->toBe('');
        expect($payload['environment'])->toHaveKey('projectRoot', cmsPath());
        expect($payload['composer'])->toHaveKey('projectRoot', cmsPath());
    } finally {
        if ($originalRoot === false) {
            putenv(ProjectContext::ENV_PROJECT_ROOT);
        } else {
            putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $originalRoot);
        }
    }
});

it('returns kirby roots via kirby://roots resource', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    $originalRoot = getenv(ProjectContext::ENV_PROJECT_ROOT);
    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . cmsPath());
    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);

    try {
        $resource = new ProjectResources();
        $payload = $resource->roots();

        expect($payload['projectRoot'])->toBe(cmsPath());
        expect($payload['roots'])->toBeArray();
        expect($payload['roots'])->toHaveKey('index');
        expect($payload['roots'])->toHaveKey('site');
        expect($payload['roots'])->toHaveKey('content');
    } finally {
        if ($originalRoot === false) {
            putenv(ProjectContext::ENV_PROJECT_ROOT);
        } else {
            putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $originalRoot);
        }
    }
});
