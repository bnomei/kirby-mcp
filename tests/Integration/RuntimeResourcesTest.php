<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Install\RuntimeCommandsInstaller;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Resources\BlueprintResources;
use Bnomei\KirbyMcp\Mcp\Resources\FileResources;
use Bnomei\KirbyMcp\Mcp\Resources\PageResources;
use Bnomei\KirbyMcp\Mcp\Resources\SiteResources;
use Bnomei\KirbyMcp\Mcp\Resources\UserResources;
use Kirby\Cms\App;

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

it('reads site content via the resource', function (): void {
    $projectRoot = cmsPath();
    ensureRuntimeCommandsInstalled($projectRoot);

    $originalRoot = getenv(ProjectContext::ENV_PROJECT_ROOT);
    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $projectRoot);

    try {
        $resource = new SiteResources();
        $payload = $resource->siteContent();

        expect($payload['ok'] ?? null)->toBeTrue();
        expect($payload['site']['title'] ?? null)->toBeString()->not()->toBe('');
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

it('reads file content via the resource template', function (): void {
    $projectRoot = cmsPath();
    ensureRuntimeCommandsInstalled($projectRoot);

    $originalRoot = getenv(ProjectContext::ENV_PROJECT_ROOT);
    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $projectRoot);

    try {
        $resource = new FileResources();
        $payload = $resource->fileContent(rawurlencode('file://mHEVVr6xtDc3gIip'));

        expect($payload['ok'] ?? null)->toBeTrue();
        expect($payload['file']['uuid'] ?? null)->toBe('file://mHEVVr6xtDc3gIip');
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

it('reads user content via the resource template', function (): void {
    $projectRoot = cmsPath();
    ensureRuntimeCommandsInstalled($projectRoot);

    $previousApp = App::instance(null, true);
    $previousErrorHandlers = captureErrorHandlers();
    $previousWhoops = App::$enableWhoops;
    App::$enableWhoops = false;
    $app = new App([
        'roots' => [
            'index' => $projectRoot,
        ],
    ]);

    ensureUser($app, 'mcp-user@example.com', [
        'city' => 'Paris',
    ]);

    $originalRoot = getenv(ProjectContext::ENV_PROJECT_ROOT);
    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $projectRoot);

    try {
        $resource = new UserResources();
        $payload = $resource->userContent(rawurlencode('mcp-user@example.com'));

        expect($payload['ok'] ?? null)->toBeTrue();
        expect($payload['user']['email'] ?? null)->toBe('mcp-user@example.com');
        expect($payload['content'])->toBeArray();
        expect($payload['cli'])->toBeArray();
    } finally {
        if ($previousApp instanceof App) {
            App::instance($previousApp);
        }
        App::$enableWhoops = $previousWhoops;
        restoreErrorHandlers($previousErrorHandlers);
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
