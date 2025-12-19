<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Dumps;

final class DumpProjectRootResolver
{
    public const ENV_PROJECT_ROOT = 'KIRBY_MCP_PROJECT_ROOT';

    public static function resolve(?string $fallback = null): string
    {
        if (is_string($fallback) && trim($fallback) !== '') {
            return trim($fallback);
        }

        $root = getenv(self::ENV_PROJECT_ROOT);
        if (is_string($root) && trim($root) !== '') {
            return trim($root);
        }

        if (class_exists(\Kirby\Cms\App::class) && method_exists(\Kirby\Cms\App::class, 'instance')) {
            /** @var \Kirby\Cms\App|null $app */
            $app = \Kirby\Cms\App::instance(lazy: true);
            if ($app !== null) {
                $root = $app->root('index');
                if (is_string($root) && $root !== '') {
                    return $root;
                }
            }
        }

        $cwd = getcwd();
        if (is_string($cwd) && trim($cwd) !== '') {
            return trim($cwd);
        }

        throw new \RuntimeException('Unable to determine project root for MCP dumps.');
    }
}
