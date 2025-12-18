<?php

declare(strict_types=1);

// Kirby defines a global `e()` helper unconditionally, while some Laravel ecosystem dev dependencies
// define their own global `e()` helper conditionally. When both are installed, Composer's autoload
// file order can lead to fatal redeclare errors.
//
// In this repo we want Kirby-like template behavior (conditional echo), so we:
// 1) Disable Kirby's built-in helper via its documented override constant
// 2) Provide a Kirby-compatible `e()` implementation early (before other helper files load)

if (!defined('KIRBY_HELPER_E')) {
    define('KIRBY_HELPER_E', false);
}

if (!function_exists('e')) {
    function e(mixed $condition, mixed $value, mixed $alternative = null): void
    {
        echo $condition ? $value : $alternative;
    }
}
