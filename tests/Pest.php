<?php

declare(strict_types=1);

use Kirby\Cms\App;
use Kirby\Cms\User;

function cmsPath(): string
{
    return __DIR__ . '/cms';
}

function ensureUser(App $app, string $email, array $content = []): User
{
    return $app->impersonate('kirby', static function () use ($app, $email, $content): User {
        $user = $app->user($email);
        if ($user === null) {
            return $app->users()->create([
                'email' => $email,
                'password' => 'test1234',
                'role' => 'admin',
                'content' => $content,
            ]);
        }

        if ($content !== []) {
            $user = $user->update($content);
        }

        return $user;
    });
}

/**
 * @return array<int, callable>
 */
function captureErrorHandlers(): array
{
    $handlers = [];

    while (true) {
        $previousHandler = set_error_handler(static fn (): bool => false);
        restore_error_handler();

        if ($previousHandler === null) {
            break;
        }

        $handlers[] = $previousHandler;
        restore_error_handler();
    }

    $handlers = array_reverse($handlers);

    foreach ($handlers as $handler) {
        if (is_callable($handler)) {
            set_error_handler($handler);
        }
    }

    return $handlers;
}

/**
 * @param array<int, callable> $handlers
 */
function restoreErrorHandlers(array $handlers): void
{
    $activeHandlers = captureErrorHandlers();

    foreach ($activeHandlers as $_) {
        restore_error_handler();
    }

    foreach ($handlers as $handler) {
        if (is_callable($handler)) {
            set_error_handler($handler);
        }
    }
}

beforeEach(function (): void {
    \Bnomei\KirbyMcp\Support\StaticCache::clear();
    \Bnomei\KirbyMcp\Mcp\ToolIndex::clearCache();
    \Bnomei\KirbyMcp\Mcp\PromptIndex::clearCache();
    \Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext::clearRootsCache();
    \Bnomei\KirbyMcp\Project\ComposerInspector::clearCache();
    \Bnomei\KirbyMcp\Project\KirbyMcpConfig::clearCache();
});
