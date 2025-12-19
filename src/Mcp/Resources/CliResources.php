<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Cli\KirbyCliHelpParser;
use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Bnomei\KirbyMcp\Support\StaticCache;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceReadException;

final class CliResources
{
    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
    }

    /**
     * List Kirby CLI commands available in the current project.
     *
     * @return array{
     *   ok: bool,
     *   projectRoot: string,
     *   host: string|null,
     *   cliVersion: string|null,
     *   sections: array<string, array<int, string>>,
     *   commands: array<int, string>,
     *   cli: array{exitCode:int, stdout:string, stderr:string, timedOut:bool}
     * }
     */
    #[McpResource(
        uri: 'kirby://commands',
        name: 'commands',
        description: 'Kirby CLI command list for this project (parsed from `kirby help`).',
        mimeType: 'application/json',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to list Kirby CLI commands available in the project (parsed from `kirby help`).',
        keywords: [
            'cli' => 100,
            'commands' => 100,
            'command' => 80,
            'help' => 40,
            'kirby' => 30,
        ],
    )]
    public function commands(): array
    {
        $projectRoot = $this->context->projectRoot();
        $host = $this->context->kirbyHost();

        $ttlSeconds = KirbyMcpConfig::load($projectRoot)->cacheTtlSeconds();
        $cacheKey = self::cacheKey('commands', $projectRoot, $host);
        if ($ttlSeconds > 0) {
            $cached = StaticCache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $env = [];
        if (is_string($host) && trim($host) !== '') {
            $env['KIRBY_HOST'] = trim($host);
        }

        $cliResult = (new KirbyCliRunner())->run(
            projectRoot: $projectRoot,
            args: ['help'],
            env: $env,
            timeoutSeconds: 30,
        );

        $parsed = KirbyCliHelpParser::parse($cliResult->stdout);

        $payload = [
            'ok' => $cliResult->exitCode === 0 && $cliResult->timedOut === false,
            'projectRoot' => $projectRoot,
            'host' => $host,
            'cliVersion' => $parsed['cliVersion'],
            'sections' => $parsed['sections'],
            'commands' => $parsed['commands'],
            'cli' => $cliResult->toArray(),
        ];

        if ($payload['ok'] === true && $ttlSeconds > 0) {
            StaticCache::set($cacheKey, $payload, $ttlSeconds);
        }

        return $payload;
    }

    /**
     * Return parsed help output for one Kirby CLI command (`kirby <command> --help`).
     *
     * @return array{
     *   ok: bool,
     *   projectRoot: string,
     *   host: string|null,
     *   command: string,
     *   description: string|null,
     *   usage: string|null,
     *   args: array{
     *     required: array<int, array{name:string, kind:'argument'|'option', required:bool, noValue:bool, flags:array<int,string>, description:string|null}>,
     *     optional: array<int, array{name:string, kind:'argument'|'option', required:bool, noValue:bool, flags:array<int,string>, description:string|null}>
     *   },
     *   cli: array{exitCode:int, stdout:string, stderr:string, timedOut:bool}
     * }
     */
    #[McpResourceTemplate(
        uriTemplate: 'kirby://cli/command/{command}',
        name: 'cli_command',
        description: 'Parsed help output for a single Kirby CLI command via `kirby <command> --help` (e.g. backup, uuid:generate).',
        mimeType: 'application/json',
    )]
    #[McpToolIndex(
        whenToUse: 'Use to read the parsed `kirby <command> --help` output for one Kirby CLI command (args/options).',
        keywords: [
            'cli' => 100,
            'command' => 100,
            'help' => 80,
            'args' => 40,
            'options' => 40,
            'usage' => 40,
        ],
    )]
    public function command(string $command): array
    {
        $projectRoot = $this->context->projectRoot();
        $host = $this->context->kirbyHost();

        $command = trim(rawurldecode($command));
        if ($command === '') {
            throw new ResourceReadException('Command must not be empty.');
        }

        if (preg_match('/\\s/u', $command) === 1) {
            throw new ResourceReadException('Command must not contain whitespace.');
        }

        $ttlSeconds = KirbyMcpConfig::load($projectRoot)->cacheTtlSeconds();
        $cacheKey = self::cacheKey('command:' . $command, $projectRoot, $host);
        if ($ttlSeconds > 0) {
            $cached = StaticCache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $env = [];
        if (is_string($host) && trim($host) !== '') {
            $env['KIRBY_HOST'] = trim($host);
        }

        $cliResult = (new KirbyCliRunner())->run(
            projectRoot: $projectRoot,
            args: [$command, '--help'],
            env: $env,
            timeoutSeconds: 30,
        );

        $parsed = KirbyCliHelpParser::parseCommandUsage($cliResult->stdout);

        $required = self::normalizeCliArgs($parsed['required'], true);
        $optional = self::normalizeCliArgs($parsed['optional'], false);

        $payload = [
            'ok' => $cliResult->exitCode === 0 && $cliResult->timedOut === false,
            'projectRoot' => $projectRoot,
            'host' => $host,
            'command' => $command,
            'description' => $parsed['description'],
            'usage' => $parsed['usage'],
            'args' => [
                'required' => $required,
                'optional' => $optional,
            ],
            'cli' => $cliResult->toArray(),
        ];

        if ($payload['ok'] === true && $ttlSeconds > 0) {
            StaticCache::set($cacheKey, $payload, $ttlSeconds);
        }

        return $payload;
    }

    private static function cacheKey(string $kind, string $projectRoot, ?string $host): string
    {
        $hostPart = is_string($host) ? trim($host) : '';

        return 'cli:' . $kind . ':' . sha1(rtrim($projectRoot, DIRECTORY_SEPARATOR) . '|' . $hostPart);
    }

    /**
     * @param array<int, array{name:string, kind:'argument'|'option', aliases:array<int,string>, description:string|null}> $args
     * @return array<int, array{name:string, kind:'argument'|'option', required:bool, noValue:bool, flags:array<int,string>, description:string|null}>
     */
    private static function normalizeCliArgs(array $args, bool $required): array
    {
        $normalized = [];

        foreach ($args as $arg) {
            $name = $arg['name'] ?? null;
            if (!is_string($name) || $name === '') {
                continue;
            }

            $kind = $arg['kind'] ?? 'argument';
            if ($kind !== 'argument' && $kind !== 'option') {
                $kind = 'argument';
            }

            $aliases = $arg['aliases'] ?? [];
            if (!is_array($aliases)) {
                $aliases = [];
            }

            $description = $arg['description'] ?? null;
            $description = is_string($description) ? trim($description) : null;
            if ($description === '') {
                $description = null;
            }

            $flags = [];
            $noValue = true;

            if ($kind === 'option') {
                foreach ($aliases as $alias) {
                    if (!is_string($alias)) {
                        continue;
                    }

                    $alias = trim($alias);
                    if ($alias === '') {
                        continue;
                    }

                    $parts = preg_split('/\\s+/', $alias) ?: [$alias];
                    $flag = $parts[0] ?? '';
                    if (!is_string($flag) || $flag === '' || !str_starts_with($flag, '-')) {
                        continue;
                    }

                    $flags[] = $flag;

                    if (count($parts) > 1) {
                        $noValue = false;
                    }
                }

                $flags = array_values(array_unique($flags));

                $canonical = null;
                foreach ($flags as $flag) {
                    if (str_starts_with($flag, '--')) {
                        $canonical = substr($flag, 2);
                        break;
                    }
                }

                if ($canonical === null && isset($flags[0])) {
                    $canonical = ltrim($flags[0], '-');
                }

                if (is_string($canonical) && $canonical !== '') {
                    $name = $canonical;
                }
            } else {
                $noValue = false;
                $flags = [];
            }

            $normalized[] = [
                'name' => $name,
                'kind' => $kind,
                'required' => $required,
                'noValue' => $noValue,
                'flags' => $flags,
                'description' => $description,
            ];
        }

        return $normalized;
    }
}
