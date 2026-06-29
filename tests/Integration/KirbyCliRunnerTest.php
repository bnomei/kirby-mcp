<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;

function restoreKirbyCliRunnerEnv(string $key, string|false $value): void
{
    if ($value === false) {
        putenv($key);

        return;
    }

    putenv($key . '=' . $value);
}

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
    $kirbyBinary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($kirbyBinary)->not()->toBeFalse();

    $originalKirbyBin = getenv(KirbyCliRunner::ENV_KIRBY_BIN);
    $originalPhpBinary = getenv(KirbyCliRunner::ENV_PHP_BINARY);
    $stub = tempnam(sys_get_temp_dir(), 'kirby-mcp-php-binary-');
    $marker = tempnam(sys_get_temp_dir(), 'kirby-mcp-php-binary-used-');
    expect($stub)->not()->toBeFalse()
        ->and($marker)->not()->toBeFalse();

    file_put_contents(
        (string) $stub,
        "#!/bin/sh\nprintf used > " . escapeshellarg((string) $marker) . "\nexec " . escapeshellarg(PHP_BINARY) . ' "$@"' . "\n",
    );
    chmod((string) $stub, 0755);
    file_put_contents((string) $marker, '');

    try {
        putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $kirbyBinary);
        putenv(KirbyCliRunner::ENV_PHP_BINARY . '= "' . $stub . '" ');

        $result = (new KirbyCliRunner())->run(projectRoot: cmsPath(), args: ['version'], timeoutSeconds: 30);

        expect($result->exitCode)->toBe(0)
            ->and($result->timedOut)->toBeFalse()
            ->and($result->stdout)->toMatch('/\\b\\d+\\.\\d+\\.\\d+\\b/')
            ->and(file_get_contents((string) $marker))->toBe('used');
    } finally {
        restoreKirbyCliRunnerEnv(KirbyCliRunner::ENV_KIRBY_BIN, $originalKirbyBin);
        restoreKirbyCliRunnerEnv(KirbyCliRunner::ENV_PHP_BINARY, $originalPhpBinary);
        unlink((string) $stub);
        unlink((string) $marker);
    }
});
