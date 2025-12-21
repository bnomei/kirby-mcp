<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Dumps\DumpProjectRootResolver;
use Kirby\Cms\App;

/**
 * @return array{0: Closure(): ?App, 1: Closure(?App): void}
 */
function dumpProjectRootAppAccessors(): array
{
    $property = new ReflectionProperty(App::class, 'instance');
    $property->setAccessible(true);

    $getter = static function () use ($property): ?App {
        $value = $property->getValue();

        return $value instanceof App ? $value : null;
    };

    $setter = static function (?App $instance) use ($property): void {
        $property->setValue(null, $instance);
    };

    return [$getter, $setter];
}

it('prefers the explicit fallback over env', function (): void {
    $original = getenv(DumpProjectRootResolver::ENV_PROJECT_ROOT);
    putenv(DumpProjectRootResolver::ENV_PROJECT_ROOT . '= /tmp/env-root ');

    $fallback = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fallback-root';

    try {
        expect(DumpProjectRootResolver::resolve('  ' . $fallback . '  '))->toBe($fallback);
    } finally {
        if ($original === false) {
            putenv(DumpProjectRootResolver::ENV_PROJECT_ROOT);
        } else {
            putenv(DumpProjectRootResolver::ENV_PROJECT_ROOT . '=' . $original);
        }
    }
});

it('uses the env root when fallback is empty', function (): void {
    $original = getenv(DumpProjectRootResolver::ENV_PROJECT_ROOT);
    $envRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'env-root';
    putenv(DumpProjectRootResolver::ENV_PROJECT_ROOT . '=' . $envRoot);

    try {
        expect(DumpProjectRootResolver::resolve(null))->toBe($envRoot);
    } finally {
        if ($original === false) {
            putenv(DumpProjectRootResolver::ENV_PROJECT_ROOT);
        } else {
            putenv(DumpProjectRootResolver::ENV_PROJECT_ROOT . '=' . $original);
        }
    }
});

it('uses Kirby app root when available', function (): void {
    $originalEnv = getenv(DumpProjectRootResolver::ENV_PROJECT_ROOT);
    putenv(DumpProjectRootResolver::ENV_PROJECT_ROOT);

    [$getInstance, $setInstance] = dumpProjectRootAppAccessors();
    $previousInstance = $getInstance();
    $previousWhoops = App::$enableWhoops;
    App::$enableWhoops = false;

    $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-app-root-' . bin2hex(random_bytes(4));
    $app = new App(['roots' => ['index' => $root]]);
    $setInstance($app);

    try {
        expect(DumpProjectRootResolver::resolve(null))->toBe($root);
    } finally {
        App::$enableWhoops = $previousWhoops;
        $setInstance($previousInstance);
        if ($originalEnv === false) {
            putenv(DumpProjectRootResolver::ENV_PROJECT_ROOT);
        } else {
            putenv(DumpProjectRootResolver::ENV_PROJECT_ROOT . '=' . $originalEnv);
        }
    }
});

it('falls back to current working directory when no env or app root', function (): void {
    $originalEnv = getenv(DumpProjectRootResolver::ENV_PROJECT_ROOT);
    putenv(DumpProjectRootResolver::ENV_PROJECT_ROOT);

    [$getInstance, $setInstance] = dumpProjectRootAppAccessors();
    $previousInstance = $getInstance();
    $setInstance(null);

    $originalCwd = getcwd();
    $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-cwd-' . bin2hex(random_bytes(4));
    mkdir($tempDir, 0777, true);
    chdir($tempDir);

    try {
        $resolved = DumpProjectRootResolver::resolve(null);
        expect(realpath($resolved))->toBe(realpath($tempDir));
    } finally {
        if (is_string($originalCwd) && $originalCwd !== '') {
            chdir($originalCwd);
        }

        @rmdir($tempDir);
        $setInstance($previousInstance);
        if ($originalEnv === false) {
            putenv(DumpProjectRootResolver::ENV_PROJECT_ROOT);
        } else {
            putenv(DumpProjectRootResolver::ENV_PROJECT_ROOT . '=' . $originalEnv);
        }
    }
});
