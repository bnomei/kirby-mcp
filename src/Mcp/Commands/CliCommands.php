<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Commands;

use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommand;
use Kirby\CLI\CLI;
use Throwable;

final class CliCommands extends RuntimeCommand
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
            'description' => 'Lists available Kirby CLI commands (core/global/custom/plugin) with descriptions + argument metadata (structured output for MCP; prefer MCP resources `kirby://commands` and `kirby://cli/command/{command}`).',
            'args' => [
                'idsOnly' => [
                    'longPrefix' => 'ids-only',
                    'description' => 'Return only command ids (minimal payload).',
                    'noValue' => true,
                ],
            ],
            'command' => [self::class, 'run'],
        ];
    }

    public static function run(CLI $cli): void
    {
        try {
            $idsOnly = $cli->arg('idsOnly') === true;

            $commandsByCategory = $cli->commands();
            $categories = ['core', 'global', 'custom', 'plugins'];

            /** @var array<string, string> $categoryById */
            $categoryById = [];
            foreach ($categories as $category) {
                $list = $commandsByCategory[$category] ?? [];
                if (!is_array($list)) {
                    continue;
                }

                foreach ($list as $id) {
                    if (is_string($id) && $id !== '' && !isset($categoryById[$id])) {
                        $categoryById[$id] = $category;
                    }
                }
            }

            $ids = array_keys($categoryById);
            sort($ids);

            $commands = [];
            $errors = [];

            foreach ($ids as $id) {
                if ($idsOnly === true) {
                    $commands[] = [
                        'id' => $id,
                        'category' => $categoryById[$id] ?? null,
                    ];
                    continue;
                }

                try {
                    $definition = $cli->load($id);
                    if (!is_array($definition)) {
                        throw new \RuntimeException('Invalid command definition (expected array).');
                    }

                    $description = $definition['description'] ?? null;
                    $description = is_string($description) ? trim($description) : null;

                    $argsRaw = $definition['args'] ?? [];
                    if (!is_array($argsRaw)) {
                        $argsRaw = [];
                    }

                    $required = [];
                    $optional = [];

                    foreach ($argsRaw as $name => $config) {
                        if (!is_string($name) || $name === '' || !is_array($config)) {
                            continue;
                        }

                        $normalized = self::normalizeArg($name, $config);
                        if ($normalized['required'] === true) {
                            $required[] = $normalized;
                        } else {
                            $optional[] = $normalized;
                        }
                    }

                    $commands[] = [
                        'id' => $id,
                        'category' => $categoryById[$id] ?? null,
                        'description' => $description,
                        'args' => [
                            'required' => $required,
                            'optional' => $optional,
                        ],
                    ];
                } catch (Throwable $exception) {
                    $errors[] = [
                        'id' => $id,
                        'error' => self::errorArray($exception, self::traceForCli($cli, $exception)),
                    ];
                }
            }

            self::emit($cli, [
                'ok' => true,
                'idsOnly' => $idsOnly,
                'cliVersion' => $cli->version(),
                'categories' => $commandsByCategory,
                'globalArgs' => self::globalArgs(),
                'commands' => $commands,
                'counts' => [
                    'total' => count($ids),
                    'errors' => count($errors),
                ],
                'errors' => $errors,
            ]);
        } catch (Throwable $exception) {
            self::emit($cli, [
                'ok' => false,
                'error' => self::errorArray($exception, self::traceForCli($cli, $exception)),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $config
     * @return array{
     *   name: string,
     *   kind: 'argument'|'option',
     *   required: bool,
     *   noValue: bool,
     *   flags: array<int, string>,
     *   description: string|null
     * }
     */
    private static function normalizeArg(string $name, array $config): array
    {
        $description = $config['description'] ?? null;
        $description = is_string($description) ? trim($description) : null;
        if ($description === '') {
            $description = null;
        }

        $required = ($config['required'] ?? false) === true;
        $noValue = ($config['noValue'] ?? false) === true;

        $flags = [];

        $longPrefix = $config['longPrefix'] ?? null;
        if (is_string($longPrefix) && trim($longPrefix) !== '') {
            $flags[] = '--' . trim($longPrefix);
        }

        $prefix = $config['prefix'] ?? null;
        if (is_string($prefix) && trim($prefix) !== '') {
            $flags[] = '-' . trim($prefix);
        }

        $kind = $flags !== [] ? 'option' : 'argument';

        return [
            'name' => $name,
            'kind' => $kind,
            'required' => $required,
            'noValue' => $noValue,
            'flags' => $flags,
            'description' => $description,
        ];
    }

    /**
     * @return array{
     *   required: array<int, array{name:string, kind:'argument', required:true, noValue:false, flags:array<int,string>, description:string|null}>,
     *   optional: array<int, array{name:string, kind:'option', required:false, noValue:true, flags:array<int,string>, description:string|null}>
     * }
     */
    private static function globalArgs(): array
    {
        return [
            'required' => [
                [
                    'name' => 'command',
                    'kind' => 'argument',
                    'required' => true,
                    'noValue' => false,
                    'flags' => [],
                    'description' => 'The name of the command',
                ],
            ],
            'optional' => [
                [
                    'name' => 'quiet',
                    'kind' => 'option',
                    'required' => false,
                    'noValue' => true,
                    'flags' => ['--quiet'],
                    'description' => 'Surpresses any output',
                ],
                [
                    'name' => 'debug',
                    'kind' => 'option',
                    'required' => false,
                    'noValue' => true,
                    'flags' => ['--debug', '-d'],
                    'description' => 'Enables debug mode',
                ],
                [
                    'name' => 'help',
                    'kind' => 'option',
                    'required' => false,
                    'noValue' => true,
                    'flags' => ['--help', '-h'],
                    'description' => 'Prints a usage statement',
                ],
            ],
        ];
    }
}
