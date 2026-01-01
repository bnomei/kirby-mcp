<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Tools\RuntimeTools;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\RequestContext;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;

it('reads home page content via runtime CLI', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new RuntimeTools();

    $install = $tools->runtimeInstall(force: true);
    $commandsRoot = $install['commandsRoot'];

    try {
        $result = $tools->readPageContent();

        expect($result)->toHaveKey('ok', true);
        expect($result['content'])->toBeArray();
        expect($result['content'])->toHaveKey('title', 'Home');

        $session = new Session(new InMemorySessionStore(60));
        $request = (new CallToolRequest('kirby_read_page_content', []))->withId('test');
        $context = new RequestContext($session, $request);
        $structured = $tools->readPageContent(context: $context);

        expect($structured)->toBeInstanceOf(CallToolResult::class);
        if (!$structured instanceof CallToolResult) {
            throw new RuntimeException('Expected a CallToolResult instance.');
        }
        expect($structured->structuredContent)->toBeArray();
        expect($structured->structuredContent)->toHaveKey('ok', true);
        expect($structured->structuredContent['content'])->toHaveKey('title', 'Home');
    } finally {
        foreach ($install['installed'] as $relativePath) {
            $path = rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;
            if (is_file($path)) {
                @unlink($path);
            }
        }

        foreach ([
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'cli',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'page',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'config',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'site',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'file',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'user',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR),
        ] as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $entries = scandir($dir);
            if ($entries === false) {
                continue;
            }

            $remaining = array_diff($entries, ['.', '..']);
            if ($remaining === []) {
                rmdir($dir);
            }
        }
    }
});

it('reads page content by bare uuid (without page:// prefix) via runtime CLI', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new RuntimeTools();

    $install = $tools->runtimeInstall(force: true);
    $commandsRoot = $install['commandsRoot'];

    try {
        $home = $tools->readPageContent();

        expect($home)->toHaveKey('ok', true);
        $uuid = $home['page']['uuid'] ?? null;
        expect($uuid)->toBeString();
        expect($uuid)->toStartWith('page://');

        $bare = substr((string) $uuid, strlen('page://'));
        expect($bare)->toBeString();
        expect(trim($bare))->not()->toBe('');

        $byUuid = $tools->readPageContent(id: $bare);
        expect($byUuid)->toHaveKey('ok', true);
        expect($byUuid['page']['id'] ?? null)->toBe($home['page']['id'] ?? null);
    } finally {
        foreach ($install['installed'] as $relativePath) {
            $path = rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;
            if (is_file($path)) {
                @unlink($path);
            }
        }

        foreach ([
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'cli',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'page',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'config',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'site',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'file',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'user',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR),
        ] as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $entries = scandir($dir);
            if ($entries === false) {
                continue;
            }

            $remaining = array_diff($entries, ['.', '..']);
            if ($remaining === []) {
                rmdir($dir);
            }
        }
    }
});

it('requires payloadValidatedWithFieldSchemas before updating page content', function (): void {
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new RuntimeTools();

    // Without payloadValidatedWithFieldSchemas, update should fail with guidance
    $result = $tools->updatePageContent(
        id: 'home',
        data: ['headline' => 'Test'],
        confirm: true,
    );

    expect($result)->toHaveKey('ok', false);
    expect($result)->toHaveKey('needsSchemaValidation', true);
    expect($result)->toHaveKey('schemaRefs');
    expect($result['schemaRefs'])->toContain('kirby://blueprints/update-schema');
    expect($result['schemaRefs'])->toContain('kirby://blueprint/page/update-schema');
    expect($result['schemaRefs'])->toContain('kirby://fields/update-schema');
    expect($result['schemaRefs'])->toContain('kirby://field/{type}/update-schema');
});

it('returns dry-run preview when confirm=false for page content update', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new RuntimeTools();
    $install = $tools->runtimeInstall(force: true);
    $commandsRoot = $install['commandsRoot'];

    try {
        // With payloadValidatedWithFieldSchemas but without confirm, should get dry-run preview
        $result = $tools->updatePageContent(
            id: 'home',
            data: ['headline' => 'Dry Run Test'],
            payloadValidatedWithFieldSchemas: true,
            confirm: false,
        );

        expect($result)->toHaveKey('ok', false);
        expect($result)->toHaveKey('needsConfirm', true);
        expect($result)->toHaveKey('updatedKeys');
        expect($result['updatedKeys'])->toContain('headline');
        expect($result)->toHaveKey('page');
        expect($result['page'])->toHaveKey('id', 'home');
    } finally {
        foreach ($install['installed'] as $relativePath) {
            $path = rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;
            if (is_file($path)) {
                @unlink($path);
            }
        }

        foreach ([
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'cli',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'page',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'config',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'site',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'file',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'user',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp',
        ] as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $entries = scandir($dir);
            if ($entries === false) {
                continue;
            }

            $remaining = array_diff($entries, ['.', '..']);
            if ($remaining === []) {
                rmdir($dir);
            }
        }
    }
});

it('updates page content via runtime CLI (confirm=true) and restores fixture', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    $projectRoot = cmsPath();
    $homeContentFile = $projectRoot . '/content/home/home.txt';
    $original = file_get_contents($homeContentFile);
    expect($original)->toBeString();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . $projectRoot);

    $tools = new RuntimeTools();

    $install = $tools->runtimeInstall(force: true);
    $commandsRoot = $install['commandsRoot'];

    try {
        $update = $tools->updatePageContent(
            id: 'home',
            data: ['headline' => 'MCP Test Headline'],
            payloadValidatedWithFieldSchemas: true,
            confirm: true,
        );

        expect($update)->toHaveKey('ok', true);

        $read = $tools->readPageContent(id: 'home');
        expect($read)->toHaveKey('ok', true);
        expect($read['content']['headline'] ?? null)->toBe('MCP Test Headline');

        // Test passing JSON string - edge case handling
        $updateJsonString = $tools->updatePageContent(
            id: 'home',
            data: '{"headline":"MCP Test Headline (JSON string)"}',
            payloadValidatedWithFieldSchemas: true,
            confirm: true,
        );

        expect($updateJsonString)->toHaveKey('ok', true);

        $readJsonString = $tools->readPageContent(id: 'home');
        expect($readJsonString)->toHaveKey('ok', true);
        expect($readJsonString['content']['headline'] ?? null)->toBe('MCP Test Headline (JSON string)');
    } finally {
        if (is_string($original)) {
            file_put_contents($homeContentFile, $original);
        }

        foreach ($install['installed'] as $relativePath) {
            $path = rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;
            if (is_file($path)) {
                @unlink($path);
            }
        }

        foreach ([
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'cli',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'page',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'config',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'site',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'file',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'user',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR),
        ] as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $entries = scandir($dir);
            if ($entries === false) {
                continue;
            }

            $remaining = array_diff($entries, ['.', '..']);
            if ($remaining === []) {
                rmdir($dir);
            }
        }
    }
});

it('lists loaded blueprints via runtime CLI', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new RuntimeTools();

    $install = $tools->runtimeInstall(force: true);
    $commandsRoot = $install['commandsRoot'];

    try {
        $result = $tools->blueprintsLoaded();

        expect($result)->toHaveKey('ok', true);
        expect($result)->toHaveKey('counts');
        expect($result['counts']['total'] ?? 0)->toBeGreaterThan(0);
        expect($result)->toHaveKey('blueprints');
        expect($result['blueprints'])->toBeArray();
    } finally {
        foreach ($install['installed'] as $relativePath) {
            $path = rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;
            if (is_file($path)) {
                @unlink($path);
            }
        }

        foreach ([
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'cli',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'page',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'config',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'site',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'file',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'user',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp',
            rtrim($commandsRoot, DIRECTORY_SEPARATOR),
        ] as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            $entries = scandir($dir);
            if ($entries === false) {
                continue;
            }

            $remaining = array_diff($entries, ['.', '..']);
            if ($remaining === []) {
                rmdir($dir);
            }
        }
    }
});
