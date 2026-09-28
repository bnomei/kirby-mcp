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
            'description' => 'Install Kirby MCP runtime commands and permission plugin adapter into this project',
            'args' => [
                'force' => [
                    'longPrefix' => 'force',
                    'description' => 'Overwrite existing runtime command and plugin adapter files.',
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

        $commandsRoot = self::resolveCommandsRoot($cli);
        $pluginsRoot = $kirby->root('plugins');

        $projectRoot = $cli->dir();

        $result = (new RuntimeCommandsInstaller())->install(
            projectRoot: $projectRoot,
            force: $force,
            commandsRootOverride: $commandsRoot,
            pluginsRootOverride: $pluginsRoot,
        );

        if ($result->errors !== []) {
            $cli->climate()->error('Kirby MCP runtime files installed with errors.');
            foreach ($result->errors as $error) {
                $cli->climate()->error($error['path'] . ': ' . $error['error']);
            }
        } else {
            $cli->climate()->green('Kirby MCP runtime files installed.');
        }

        $cli->climate()->out('Commands target: ' . rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp');
        $cli->climate()->out('Commands installed: ' . count($result->installed));
        $cli->climate()->out('Commands skipped: ' . count($result->skipped));
        $cli->climate()->out('Plugin target: ' . $result->plugin->root);
        $cli->climate()->out('Plugin files installed: ' . count($result->plugin->installed));
        $cli->climate()->out('Plugin files skipped: ' . count($result->plugin->skipped));
    }
}
