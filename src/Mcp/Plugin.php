<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Kirby\Cms\App;
use Kirby\Http\Response;

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
                'api' => [
                    'routes' => [[
                        'pattern' => 'kirby-mcp/activity',
                        'method' => 'GET',
                        'action' => function (): Response {
                            $app = App::instance();
                            if ($app->user()?->isAdmin() !== true) {
                                throw new \Kirby\Exception\PermissionException();
                            }

                            return Response::json(
                                (new Activity($app->roots()->index()))->status(),
                                headers: ['Cache-Control' => 'private, no-store'],
                            );
                        },
                    ]],
                ],
            ],
            root: $root,
        );
    }
}
