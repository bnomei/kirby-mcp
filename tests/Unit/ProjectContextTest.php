<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;

it('uses the env project root when set', function (): void {
    $original = getenv(ProjectContext::ENV_PROJECT_ROOT);
    $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-root-' . bin2hex(random_bytes(4));

    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $root);

    try {
        expect((new ProjectContext())->projectRoot())->toBe($root);
    } finally {
        if ($original === false) {
            putenv(ProjectContext::ENV_PROJECT_ROOT);
        } else {
            putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $original);
        }
    }
});

it('falls back to the current working directory when no env/project root is found', function (): void {
    $original = getenv(ProjectContext::ENV_PROJECT_ROOT);
    putenv(ProjectContext::ENV_PROJECT_ROOT);

    $cwd = getcwd();
    $temp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-project-context-' . bin2hex(random_bytes(4));
    mkdir($temp, 0777, true);
    chdir($temp);

    try {
        expect(realpath((new ProjectContext())->projectRoot()))->toBe(realpath($temp));
    } finally {
        if (is_string($cwd) && $cwd !== '') {
            chdir($cwd);
        }
        @rmdir($temp);
        if ($original === false) {
            putenv(ProjectContext::ENV_PROJECT_ROOT);
        } else {
            putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $original);
        }
    }
});

it('prefers KIRBY_MCP_HOST over KIRBY_HOST', function (): void {
    $originalMcpHost = getenv(ProjectContext::ENV_HOST);
    $originalKirbyHost = getenv('KIRBY_HOST');

    putenv(ProjectContext::ENV_HOST . '=https://mcp-host.test');
    putenv('KIRBY_HOST=https://kirby-host.test');

    try {
        expect((new ProjectContext())->kirbyHost())->toBe('https://mcp-host.test');
    } finally {
        if ($originalMcpHost === false) {
            putenv(ProjectContext::ENV_HOST);
        } else {
            putenv(ProjectContext::ENV_HOST . '=' . $originalMcpHost);
        }

        if ($originalKirbyHost === false) {
            putenv('KIRBY_HOST');
        } else {
            putenv('KIRBY_HOST=' . $originalKirbyHost);
        }
    }
});

it('uses KIRBY_HOST when KIRBY_MCP_HOST is not set', function (): void {
    $originalMcpHost = getenv(ProjectContext::ENV_HOST);
    $originalKirbyHost = getenv('KIRBY_HOST');

    putenv(ProjectContext::ENV_HOST);
    putenv('KIRBY_HOST=https://kirby-host.test');

    try {
        expect((new ProjectContext())->kirbyHost())->toBe('https://kirby-host.test');
    } finally {
        if ($originalMcpHost === false) {
            putenv(ProjectContext::ENV_HOST);
        } else {
            putenv(ProjectContext::ENV_HOST . '=' . $originalMcpHost);
        }

        if ($originalKirbyHost === false) {
            putenv('KIRBY_HOST');
        } else {
            putenv('KIRBY_HOST=' . $originalKirbyHost);
        }
    }
});

it('falls back to config host when env vars are missing', function (): void {
    $originalRoot = getenv(ProjectContext::ENV_PROJECT_ROOT);
    $originalMcpHost = getenv(ProjectContext::ENV_HOST);
    $originalKirbyHost = getenv('KIRBY_HOST');

    $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-project-context-config-' . bin2hex(random_bytes(4));
    $configDir = $root . DIRECTORY_SEPARATOR . '.kirby-mcp';
    mkdir($configDir, 0777, true);
    file_put_contents($configDir . DIRECTORY_SEPARATOR . 'mcp.json', json_encode([
        'kirby' => ['host' => 'https://config-host.test'],
    ], JSON_PRETTY_PRINT));

    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $root);
    putenv(ProjectContext::ENV_HOST);
    putenv('KIRBY_HOST');
    KirbyMcpConfig::clearCache();

    try {
        expect((new ProjectContext())->kirbyHost())->toBe('https://config-host.test');
    } finally {
        KirbyMcpConfig::clearCache();
        @unlink($configDir . DIRECTORY_SEPARATOR . 'mcp.json');
        @rmdir($configDir);
        @rmdir($root);

        if ($originalRoot === false) {
            putenv(ProjectContext::ENV_PROJECT_ROOT);
        } else {
            putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $originalRoot);
        }

        if ($originalMcpHost === false) {
            putenv(ProjectContext::ENV_HOST);
        } else {
            putenv(ProjectContext::ENV_HOST . '=' . $originalMcpHost);
        }

        if ($originalKirbyHost === false) {
            putenv('KIRBY_HOST');
        } else {
            putenv('KIRBY_HOST=' . $originalKirbyHost);
        }
    }
});
