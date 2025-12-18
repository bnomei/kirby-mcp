<?php

declare(strict_types=1);

// PHPStan runs in parallel worker processes. Using `-d auto_prepend_file=...` on the main
// process does not propagate to workers, so we must ensure Kirby helper overrides are
// applied via PHPStan's `--autoload-file`, which workers inherit.
if (!defined('KIRBY_HELPER_E')) {
    define('KIRBY_HELPER_E', false);
}

if (!defined('KIRBY_HELPER_DUMP')) {
    define('KIRBY_HELPER_DUMP', false);
}

// Provide Kirby's conditional echo helper early so Laravel's `e()` helper won't be defined.
if (!function_exists('e')) {
    function e(mixed $condition, mixed $value, mixed $alternative = null): void
    {
        echo $condition ? $value : $alternative;
    }
}

return require __DIR__ . '/../vendor/autoload.php';
