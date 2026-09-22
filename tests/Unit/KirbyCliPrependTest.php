<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

it('ensures Kirby helper constants are disabled by the CLI prepend', function (): void {
    require_once __DIR__ . '/../../src/Cli/kirby-cli-prepend.php';

    expect(defined('KIRBY_HELPER_DUMP'))->toBeTrue();
    expect(constant('KIRBY_HELPER_DUMP'))->toBeFalse();
    expect(defined('KIRBY_HELPER_E'))->toBeTrue();
    expect(constant('KIRBY_HELPER_E'))->toBeFalse();
});

it('provides Kirby conditional echo behavior for CLI-rendered templates', function (): void {
    $prepend = realpath(__DIR__ . '/../../src/Cli/kirby-cli-prepend.php');
    expect($prepend)->not()->toBeFalse();

    $process = new Process([
        PHP_BINARY,
        '-r',
        'require $argv[1]; e(true, "yes", "no"); e(false, "yes", "no");',
        $prepend,
    ]);
    $process->run();

    expect($process->getExitCode())->toBe(0);
    expect($process->getErrorOutput())->toBe('');
    expect($process->getOutput())->toBe('yesno');
});
