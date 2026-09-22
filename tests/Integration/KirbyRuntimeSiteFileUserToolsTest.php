<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Tools\RuntimeTools;
use Kirby\Cms\App;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Discovery\SchemaValidator;

it('reads site content via runtime CLI', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new RuntimeTools();
    $install = $tools->runtimeInstall(force: true);
    $commandsRoot = $install['commandsRoot'];

    try {
        $result = $tools->readSiteContent();

        expect($result)->toHaveKey('ok', true);
        expect($result['content'])->toBeArray();
        expect($result['site'])->toHaveKey('title');
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
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'query',
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

it('reads file content via runtime CLI', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new RuntimeTools();
    $install = $tools->runtimeInstall(force: true);
    $commandsRoot = $install['commandsRoot'];

    try {
        $result = $tools->readFileContent(id: 'file://mHEVVr6xtDc3gIip');

        expect($result)->toHaveKey('ok', true);
        expect($result['file']['uuid'] ?? null)->toBe('file://mHEVVr6xtDc3gIip');
        expect($result['content'])->toBeArray();
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
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'query',
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

it('reads user content via runtime CLI', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new RuntimeTools();
    $install = $tools->runtimeInstall(force: true);
    $commandsRoot = $install['commandsRoot'];

    $previousErrorHandlers = captureErrorHandlers();
    $previousWhoops = App::$enableWhoops;
    App::$enableWhoops = false;

    $previousApp = App::instance(null, true);
    $app = new App([
        'roots' => [
            'index' => cmsPath(),
        ],
    ]);

    ensureUser($app, 'mcp-runtime@example.com', [
        'city' => 'Rome',
    ]);

    try {
        $result = $tools->readUserContent(id: 'mcp-runtime@example.com');

        expect($result)->toHaveKey('ok', true);
        expect($result['user']['email'] ?? null)->toBe('mcp-runtime@example.com');
        expect($result['content'])->toBeArray();
        expect($result['fieldSchemas'])->toHaveKey('city');

        $attributes = (new ReflectionMethod(RuntimeTools::class, 'readUserContent'))->getAttributes(McpTool::class);
        expect($attributes)->toHaveCount(1);

        $outputSchema = $attributes[0]->newInstance()->outputSchema;
        if (!is_array($outputSchema)) {
            throw new RuntimeException('Expected readUserContent to declare an output schema.');
        }

        expect((new SchemaValidator())->validateAgainstJsonSchema($result, $outputSchema))->toBe([]);
    } finally {
        if ($previousApp instanceof App) {
            App::instance($previousApp);
        }
        App::$enableWhoops = $previousWhoops;
        restoreErrorHandlers($previousErrorHandlers);

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
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'query',
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

it('requires payloadValidatedWithFieldSchemas before updating site/file/user content', function (): void {
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new RuntimeTools();

    $site = $tools->updateSiteContent(
        data: ['title' => 'Test'],
        confirm: true,
    );
    expect($site)->toHaveKey('ok', false);
    expect($site)->toHaveKey('needsSchemaValidation', true);

    $file = $tools->updateFileContent(
        id: 'file://mHEVVr6xtDc3gIip',
        data: ['alt' => 'Test'],
        confirm: true,
    );
    expect($file)->toHaveKey('ok', false);
    expect($file)->toHaveKey('needsSchemaValidation', true);

    $user = $tools->updateUserContent(
        id: 'mcp-runtime@example.com',
        data: ['city' => 'Test'],
        confirm: true,
    );
    expect($user)->toHaveKey('ok', false);
    expect($user)->toHaveKey('needsSchemaValidation', true);
});

it('updates site/file/user content from JSON-encoded object strings when confirm=true', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $projectRoot = cmsPath();
    $siteContentFile = $projectRoot . '/content/site.txt';
    $fileContentFile = $projectRoot . '/content/3_about/writing.jpg.txt';

    $siteOriginal = file_get_contents($siteContentFile);
    $fileOriginal = file_get_contents($fileContentFile);
    expect($siteOriginal)->toBeString();
    expect($fileOriginal)->toBeString();

    $tools = new RuntimeTools();
    $install = $tools->runtimeInstall(force: true);
    $commandsRoot = $install['commandsRoot'];

    $previousErrorHandlers = captureErrorHandlers();
    $previousWhoops = App::$enableWhoops;
    App::$enableWhoops = false;

    $previousApp = App::instance(null, true);
    $app = new App([
        'roots' => [
            'index' => $projectRoot,
        ],
    ]);

    ensureUser($app, 'mcp-runtime-json@example.com', [
        'city' => 'Rome',
    ]);

    try {
        $siteUpdate = $tools->updateSiteContent(
            data: '{"title":"Runtime Site JSON String"}',
            payloadValidatedWithFieldSchemas: true,
            confirm: true,
        );
        expect($siteUpdate)->toHaveKey('ok', true);
        expect($siteUpdate['content']['title'] ?? null)->toBe('Runtime Site JSON String');

        $fileUpdate = $tools->updateFileContent(
            id: 'file://mHEVVr6xtDc3gIip',
            data: '{"alt":"Runtime File JSON String"}',
            payloadValidatedWithFieldSchemas: true,
            confirm: true,
        );
        expect($fileUpdate)->toHaveKey('ok', true);
        expect($fileUpdate['content']['alt'] ?? null)->toBe('Runtime File JSON String');

        $userUpdate = $tools->updateUserContent(
            id: 'mcp-runtime-json@example.com',
            data: '{"city":"Runtime User JSON String"}',
            payloadValidatedWithFieldSchemas: true,
            confirm: true,
        );
        expect($userUpdate)->toHaveKey('ok', true);
        expect($userUpdate['content']['city'] ?? null)->toBe('Runtime User JSON String');
    } finally {
        if (is_string($siteOriginal)) {
            file_put_contents($siteContentFile, $siteOriginal);
        }

        if (is_string($fileOriginal)) {
            file_put_contents($fileContentFile, $fileOriginal);
        }

        if ($previousApp instanceof App) {
            App::instance($previousApp);
        }
        App::$enableWhoops = $previousWhoops;
        restoreErrorHandlers($previousErrorHandlers);

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
            rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp' . DIRECTORY_SEPARATOR . 'query',
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
