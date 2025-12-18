<?php

declare(strict_types=1);

// This file is intended to be used with `php -d auto_prepend_file=...` so it runs
// before Composer autoload files, preventing Kirby from defining conflicting globals.
if (!defined('KIRBY_HELPER_E')) {
    define('KIRBY_HELPER_E', false);
}

if (!defined('KIRBY_HELPER_DUMP')) {
    define('KIRBY_HELPER_DUMP', false);
}
