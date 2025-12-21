<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Install\RuntimeCommandsInstaller;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Resources\BlueprintResources;
use Bnomei\KirbyMcp\Mcp\Resources\PageResources;

function ensureRuntimeCommandsInstalled(string $projectRoot): void
{
    static $installed = false;

    if ($installed) {
        return;
    }

    $commandsRoot = $projectRoot . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'commands';
    (new RuntimeCommandsInstaller())->install($projectRoot, commandsRootOverride: $commandsRoot);

    $installed = true;
}

it('reads a blueprint via the resource template', function (): void {
    $projectRoot = cmsPath();
    ensureRuntimeCommandsInstalled($projectRoot);

    $originalRoot = getenv(ProjectContext::ENV_PROJECT_ROOT);
    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $projectRoot);

    try {
        $resource = new BlueprintResources();
        $payload = $resource->blueprint(rawurlencode('pages/home'));

        expect($payload['ok'] ?? null)->toBeTrue();
        expect($payload['id'])->toBe('pages/home');
        expect($payload['type'])->toBe('pages');
        expect($payload['displayName'])->toBe('Home');
        expect($payload['file'])->toBeArray();
        expect($payload['mode'])->toBe('runtime');
        expect($payload['cliMeta'])->toBeArray();
    } finally {
        if ($originalRoot === false) {
            putenv(ProjectContext::ENV_PROJECT_ROOT);
        } else {
            putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $originalRoot);
        }
    }
});

it('reads page content via the resource template', function (): void {
    $projectRoot = cmsPath();
    ensureRuntimeCommandsInstalled($projectRoot);

    $originalRoot = getenv(ProjectContext::ENV_PROJECT_ROOT);
    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $projectRoot);

    try {
        $resource = new PageResources();
        $payload = $resource->pageContent('home');

        expect($payload['ok'] ?? null)->toBeTrue();
        expect($payload['page']['id'] ?? null)->toBe('home');
        expect($payload['content'])->toBeArray();
        expect($payload['cli'])->toBeArray();
    } finally {
        if ($originalRoot === false) {
            putenv(ProjectContext::ENV_PROJECT_ROOT);
        } else {
            putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $originalRoot);
        }
    }
});

it('rejects empty page ids in the page content resource', function (): void {
    $resource = new PageResources();
    $payload = $resource->pageContent('   ');

    expect($payload['ok'])->toBeFalse();
    expect($payload['message'])->toBe('Page id/uuid must not be empty.');
});
