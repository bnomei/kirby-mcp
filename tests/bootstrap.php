<?php

declare(strict_types=1);

// Prevent Kirby from defining global helpers that conflict with dev dependencies (e.g. Illuminate).
// These constants must be defined before Composer autoload includes Kirby's helper files.
if (!defined('KIRBY_HELPER_E')) {
    define('KIRBY_HELPER_E', false);
}

if (!defined('KIRBY_HELPER_DUMP')) {
    define('KIRBY_HELPER_DUMP', false);
}

require __DIR__ . '/../vendor/autoload.php';
