<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Runtime;

use Bnomei\KirbyMcp\Install\RuntimeCommandsInstaller;
use Kirby\CLI\CLI;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class McpUpdateCommand extends McpRuntimeCommand
{
    /**
     * @return array{
     *   description: string,
     *   command: callable(CLI): void
     * }
     */
    public static function definition(): array
    {
        return [
            'description' => 'Update Kirby MCP runtime CLI commands in this project (site/commands/mcp)',
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

        $commandsRoot = $cli->root('commands.local') ?? $cli->root('commands');
        if (!is_string($commandsRoot) || $commandsRoot === '') {
            $commandsRoot = rtrim($cli->dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'commands';
        }

        $packageRoot = dirname(__DIR__, 2);
        $sourceRoot = rtrim($packageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'commands';
        $expected = self::expectedCommandFiles($sourceRoot);
        $missing = self::missingCommandFiles($commandsRoot, $expected);

        if ($missing === []) {
            $cli->climate()->green('Kirby MCP runtime commands are already installed.');
            return;
        }

        $projectRoot = $cli->dir();

        $result = (new RuntimeCommandsInstaller())->install(
            projectRoot: $projectRoot,
            force: false,
            commandsRootOverride: $commandsRoot,
        );

        if ($result->errors !== []) {
            $cli->climate()->error('Kirby MCP runtime commands updated with errors.');
            foreach ($result->errors as $error) {
                $cli->climate()->error($error['path'] . ': ' . $error['error']);
            }
        } else {
            $cli->climate()->green('Kirby MCP runtime commands updated.');
        }

        $targetMcp = rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp';
        $cli->climate()->out('Target: ' . $targetMcp);
        $cli->climate()->out('Installed: ' . count($result->installed));
        $cli->climate()->out('Skipped: ' . count($result->skipped));
        $cli->climate()->out('Missing (before): ' . count($missing));
    }

    /**
     * @return array<int, string>
     */
    private static function expectedCommandFiles(string $sourceRoot): array
    {
        if (!is_dir($sourceRoot)) {
            return [];
        }

        $expected = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceRoot));

        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            $relative = ltrim(substr($path, strlen($sourceRoot)), DIRECTORY_SEPARATOR);
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
            if ($relative !== '') {
                $expected[] = $relative;
            }
        }

        $expected = array_values(array_unique($expected));
        sort($expected);

        return $expected;
    }

    /**
     * @param array<int, string> $expected
     * @return array<int, string>
     */
    private static function missingCommandFiles(string $commandsRoot, array $expected): array
    {
        if ($expected === []) {
            return [];
        }

        $commandsRoot = rtrim($commandsRoot, DIRECTORY_SEPARATOR);
        $missing = [];

        foreach ($expected as $relative) {
            $path = $commandsRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if (!is_file($path)) {
                $missing[] = $relative;
            }
        }

        return $missing;
    }
}
