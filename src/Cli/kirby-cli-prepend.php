<?php

declare(strict_types=1);

// Prevent Kirby from defining global helpers that can collide with dependencies.
if (!defined('KIRBY_HELPER_DUMP')) {
    define('KIRBY_HELPER_DUMP', false);
}

if (!defined('KIRBY_HELPER_E')) {
    define('KIRBY_HELPER_E', false);
}

// Templates still rely on Kirby's conditional echo helper while rendering via CLI.
if (!function_exists('e')) {
    function e(mixed $condition, mixed $value, mixed $alternative = null): void
    {
        echo $condition ? $value : $alternative;
    }
}
