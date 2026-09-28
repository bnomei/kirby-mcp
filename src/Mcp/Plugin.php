<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Kirby\Cms\App;

final class Plugin
{
    public static function register(?string $root = null): void
    {
        if ($root === null) {
            $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'] ?? null;
            $root = is_string($caller) ? dirname($caller) : dirname(__DIR__, 2);
        }

        App::plugin(
            name: 'bnomei/kirby-mcp',
            extends: [
                'permissions' => Permissions::defaults(),
            ],
            root: $root,
        );
    }
}
