<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Commands;

use Bnomei\KirbyMcp\Mcp\Permissions;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommand;
use Kirby\CLI\CLI;

final class PermissionsRead extends RuntimeCommand
{
    public static function definition(): array
    {
        return [
            'description' => 'Resolve remote MCP permissions for the authenticated Kirby user',
            'command' => [self::class, 'run'],
        ];
    }

    public static function run(CLI $cli): void
    {
        $kirby = self::kirbyOrEmitError($cli);
        if ($kirby === null) {
            return;
        }
        try {
            $id = self::mutationUser($kirby);
            if ($id === 'kirby' || $kirby->plugin('bnomei/kirby-mcp') === null) {
                throw new \RuntimeException('An existing Kirby user and the installed MCP plugin adapter are required.');
            }
            $values = $kirby->impersonate($id, static function () use ($kirby): array {
                $user = $kirby->user();
                if ($user === null) {
                    throw new \RuntimeException('The authenticated Kirby user is unavailable.');
                }
                $permissions = $user->role()->permissions();
                $values = [];
                foreach (Permissions::defaults() as $key => $default) {
                    $values[$key] = $permissions->for(Permissions::CATEGORY, $key, $default);
                }
                return $values;
            });
            self::emit($cli, ['ok' => true, 'permissions' => $values]);
        } catch (\Throwable $exception) {
            self::emit($cli, ['ok' => false, 'error' => self::errorArray($exception)]);
        }
    }
}
