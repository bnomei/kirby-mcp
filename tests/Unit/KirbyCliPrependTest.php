<?php

declare(strict_types=1);

it('ensures Kirby helper constants are disabled by the CLI prepend', function (): void {
    require_once __DIR__ . '/../../src/Cli/kirby-cli-prepend.php';

    expect(defined('KIRBY_HELPER_DUMP'))->toBeTrue();
    expect(constant('KIRBY_HELPER_DUMP'))->toBeFalse();
    expect(defined('KIRBY_HELPER_E'))->toBeTrue();
    expect(constant('KIRBY_HELPER_E'))->toBeFalse();
});
