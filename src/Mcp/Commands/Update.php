<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Commands;

use Bnomei\KirbyMcp\Install\RuntimeCommandsInstaller;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommand;
use Kirby\CLI\CLI;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class Update extends RuntimeCommand
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
            'description' => 'Update Kirby MCP runtime commands and permission plugin adapter in this project',
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

        $commandsRoot = self::resolveCommandsRoot($cli);
        $pluginsRoot = $kirby->root('plugins');

        $packageRoot = dirname(__DIR__, 2);
        $sourceRoot = rtrim($packageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'commands';
        $expected = self::expectedCommandFiles($sourceRoot);
        $missing = self::missingCommandFiles($commandsRoot, $expected);

        $projectRoot = $cli->dir();

        $result = (new RuntimeCommandsInstaller())->install(
            projectRoot: $projectRoot,
            force: true,
            commandsRootOverride: $commandsRoot,
            pluginsRootOverride: $pluginsRoot,
        );

        if ($result->errors !== []) {
            $cli->climate()->error('Kirby MCP runtime files updated with errors.');
            foreach ($result->errors as $error) {
                $cli->climate()->error($error['path'] . ': ' . $error['error']);
            }
        } else {
            $cli->climate()->green('Kirby MCP runtime files updated.');
        }

        $targetMcp = rtrim($commandsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mcp';
        $cli->climate()->out('Commands target: ' . $targetMcp);
        $cli->climate()->out('Commands installed: ' . count($result->installed));
        $cli->climate()->out('Commands skipped: ' . count($result->skipped));
        $cli->climate()->out('Commands missing (before): ' . count($missing));
        $cli->climate()->out('Plugin target: ' . $result->plugin->root);
        $cli->climate()->out('Plugin files installed: ' . count($result->plugin->installed));
        $cli->climate()->out('Plugin files skipped: ' . count($result->plugin->skipped));
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
