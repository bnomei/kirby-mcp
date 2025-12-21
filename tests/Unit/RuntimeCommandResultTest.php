<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliResult;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandResult;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandRunner;

it('returns CLI metadata and defaults parse errors', function (): void {
    $cli = new KirbyCliResult(exitCode: 1, stdout: 'out', stderr: 'err', timedOut: false);

    $result = new RuntimeCommandResult(
        projectRoot: '/project',
        host: null,
        commandsRoot: '/commands',
        expectedCommandFile: '/commands/mcp/test.php',
        installed: false,
        cliResult: $cli,
    );

    expect($result->cliMeta())->toBe([
        'exitCode' => 1,
        'timedOut' => false,
    ]);

    expect($result->cli())->toBe([
        'exitCode' => 1,
        'stdout' => 'out',
        'stderr' => 'err',
        'timedOut' => false,
    ]);

    expect($result->parseErrorString())->toBe(RuntimeCommandRunner::DEFAULT_PARSE_ERROR);

    $custom = new RuntimeCommandResult(
        projectRoot: '/project',
        host: null,
        commandsRoot: '/commands',
        expectedCommandFile: '/commands/mcp/test.php',
        installed: false,
        cliResult: null,
        payload: null,
        parseError: '  custom error ',
    );

    expect($custom->parseErrorString())->toBe('custom error');
});

it('formats runtime install and parse error responses', function (): void {
    $result = new RuntimeCommandResult(
        projectRoot: '/project',
        host: null,
        commandsRoot: '/commands',
        expectedCommandFile: '/commands/mcp/test.php',
        installed: false,
    );

    $needsInstall = $result->needsRuntimeInstallResponse(['extra' => true]);
    expect($needsInstall['ok'])->toBeFalse();
    expect($needsInstall['needsRuntimeInstall'])->toBeTrue();
    expect($needsInstall['expectedCommandFile'])->toBe('/commands/mcp/test.php');
    expect($needsInstall['extra'])->toBeTrue();

    $parseError = $result->parseErrorResponse(['context' => 'runtime']);
    expect($parseError['ok'])->toBeFalse();
    expect($parseError['parseError'])->toBe(RuntimeCommandRunner::DEFAULT_PARSE_ERROR);
    expect($parseError['context'])->toBe('runtime');
});
