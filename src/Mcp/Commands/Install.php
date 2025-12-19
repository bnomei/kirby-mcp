<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Commands;

use Bnomei\KirbyMcp\Install\RuntimeCommandsInstaller;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommand;
use Kirby\CLI\CLI;

final class Install extends RuntimeCommand
{
    /**
     * @return array{
     *   description: string,
     *   args: array<string, mixed>,
     *   command: callable(CLI): void
     * }
     */
    public static function definition(): array
    {
        return [
            'description' => 'Install Kirby MCP runtime CLI commands into this project (site/commands/mcp)',
            'args' => [
                'force' => [
                    'longPrefix' => 'force',
                    'description' => 'Overwrite existing files in site/commands/mcp.',
                    'noValue' => true,
                ],
            ],
            'command' => [self::class, 'run'],
        ];
    }

    public static function run(CLI $cli): void
    {
        $kirby = $cli->kirby(false);
        if ($kirby === null) {
            $cli->climate()->error('The Kirby installation could not be found.');
            return;
        }

        $force = $cli->arg('force') === true;

        $commandsRoot = $kirby->root('commands');
        if (!is_string($commandsRoot) || $commandsRoot === '') {
            $commandsRoot = rtrim($cli->dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'commands';
        }

        $projectRoot = $cli->dir();

        $result = (new RuntimeCommandsInstaller())->install(
            projectRoot: $projectRoot,
            force: $force,
            commandsRootOverride: $commandsRoot,
        );

        if ($result->errors !== []) {
            $cli->climate()->error('Kirby MCP runtime commands installed with errors.');
            foreach ($result->errors as $error) {
                $cli->climate()->error($error['path'] . ': ' . $error['error']);
            }
        } else {
            $cli->climate()->green('Kirby MCP runtime commands installed.');
        }

        $cli->climate()->out('Target: ' . rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp');
        $cli->climate()->out('Installed: ' . count($result->installed));
        $cli->climate()->out('Skipped: ' . count($result->skipped));
    }
}
