<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Cli\KirbyCliHelpParser;
use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Cli\McpMarkedJsonExtractor;
use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\McpLog;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;

final class CliTools
{
    /**
     * Minimal built-in allowlist for `kirby_run_cli` (read-only commands).
     *
     * @var array<int, string>
     */
    private const DEFAULT_ALLOW = [
        'help',
        'version',
        'roots',
        'security',
        'license:info',
        'uuid:duplicates',
        'mcp:render',
    ];

    /**
     * Built-in allowlist for write-capable commands when `allowWrite=true`.
     *
     * @var array<int, string>
     */
    private const DEFAULT_ALLOW_WRITE = [
        'make:*',
        'mcp:*',
        'clear:*',
    ];

    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
    }

    /**
     * List Kirby CLI commands available in the current project.
     *
     * @return array{
     *   projectRoot: string,
     *   host: string|null,
     *   cliVersion: string|null,
     *   sections: array<string, array<int, string>>,
     *   commands: array<int, string>,
     *   cli: array{exitCode:int, stdout:string, stderr:string, timedOut:bool}
     * }
     */
    #[McpToolIndex(
        whenToUse: 'Use when you need to discover which Kirby CLI commands are available in this project (parsed from `kirby help`).',
        keywords: [
            'cli' => 60,
            'commands' => 100,
            'command' => 80,
            'help' => 80,
            'list' => 60,
            'discover' => 40,
        ],
    )]
    #[McpTool(
        name: 'kirby_list_cli_commands',
        description: 'List available Kirby CLI commands by running `kirby help` and parsing the output into sections + command names.',
        annotations: new ToolAnnotations(
            title: 'List Kirby CLI Commands',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function listCliCommands(?ClientGateway $client = null): array
    {
        try {
            $projectRoot = $this->context->projectRoot();
            $host = $this->context->kirbyHost();

            $env = [];
            if (is_string($host) && $host !== '') {
                $env['KIRBY_HOST'] = $host;
            }

            $cliResult = (new KirbyCliRunner())->run(
                projectRoot: $projectRoot,
                args: ['help'],
                env: $env,
                timeoutSeconds: 30,
            );

            $parsed = KirbyCliHelpParser::parse($cliResult->stdout);

            return [
                'projectRoot' => $projectRoot,
                'host' => $host,
                'cliVersion' => $parsed['cliVersion'],
                'sections' => $parsed['sections'],
                'commands' => $parsed['commands'],
                'cli' => $cliResult->toArray(),
            ];
        } catch (\Throwable $exception) {
            McpLog::error($client, [
                'tool' => 'kirby_list_cli_commands',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }

    /**
     * Run a Kirby CLI command with guardrails (allowlist + optional project config).
     *
     * Notes:
     * - `command` is the first argument after `kirby` (do not include the `kirby` prefix).
     * - Interactive commands may time out; prefer commands that don't prompt.
     *
     * @param array<int, mixed> $arguments
     * @return array{
     *   ok: bool,
     *   projectRoot: string,
     *   host: string|null,
     *   command: string,
     *   arguments: array<int, string>,
     *   allowWrite: bool,
     *   config: array{path: string|null, error: string|null, allow: array<int, string>, allowWrite: array<int, string>, deny: array<int, string>},
     *   policy: array{matchedDeny: string|null, matchedAllow: string|null, matchedAllowWrite: string|null},
     *   message: string,
     *   cli: array{exitCode:int, stdout:string, stderr:string, timedOut:bool}|null,
     *   mcpJson: array<mixed>|null,
     *   mcpJsonError: string|null
     * }
     */
    #[McpToolIndex(
        whenToUse: 'Use to execute Kirby CLI commands (guarded by an allowlist + optional .kirby-mcp/mcp.json). Prefer this over manual PHP bootstrapping.',
        keywords: [
            'cli' => 60,
            'run' => 80,
            'command' => 80,
            'execute' => 60,
            'make' => 60,
            'scaffold' => 50,
            'generate' => 40,
            'create' => 30,
            'clear' => 30,
            'allow' => 30,
            'allowlist' => 30,
            'mcp' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_run_cli',
        description: 'Run a Kirby CLI command with an allowlist (and optional .kirby-mcp/mcp.json config). Use allowWrite=true for write-capable commands like make:* and mcp:*.',
        annotations: new ToolAnnotations(
            title: 'Run Kirby CLI',
            readOnlyHint: false,
            destructiveHint: true,
            openWorldHint: false,
        ),
    )]
    public function runCli(
        string $command,
        array $arguments = [],
        bool $allowWrite = false,
        int $timeoutSeconds = 60,
        ?ClientGateway $client = null,
    ): array {
        try {
            $projectRoot = $this->context->projectRoot();
            $host = $this->context->kirbyHost();

            $command = trim($command);
            if ($command === '') {
                return [
                    'ok' => false,
                    'projectRoot' => $projectRoot,
                    'host' => $host,
                    'command' => $command,
                    'arguments' => [],
                    'allowWrite' => $allowWrite,
                    'config' => [
                        'path' => null,
                        'error' => null,
                        'allow' => [],
                        'allowWrite' => [],
                        'deny' => [],
                    ],
                    'policy' => [
                        'matchedDeny' => null,
                        'matchedAllow' => null,
                        'matchedAllowWrite' => null,
                    ],
                    'message' => 'Command must not be empty.',
                    'cli' => null,
                    'mcpJson' => null,
                    'mcpJsonError' => null,
                ];
            }

            $normalizedArgs = $this->normalizeArguments($arguments);

            $config = KirbyMcpConfig::load($projectRoot);

            $deny = $config->cliDeny();
            $allow = array_values(array_unique(array_merge(self::DEFAULT_ALLOW, $config->cliAllow())));
            $allowWritePatterns = array_values(array_unique(array_merge(self::DEFAULT_ALLOW_WRITE, $config->cliAllowWrite())));

            $matchedDeny = $this->firstMatchingPattern($command, $deny);
            if ($matchedDeny !== null) {
                return [
                    'ok' => false,
                    'projectRoot' => $projectRoot,
                    'host' => $host,
                    'command' => $command,
                    'arguments' => $normalizedArgs,
                    'allowWrite' => $allowWrite,
                    'config' => [
                        'path' => $config->path,
                        'error' => $config->error,
                        'allow' => $config->cliAllow(),
                        'allowWrite' => $config->cliAllowWrite(),
                        'deny' => $deny,
                    ],
                    'policy' => [
                        'matchedDeny' => $matchedDeny,
                        'matchedAllow' => null,
                        'matchedAllowWrite' => null,
                    ],
                    'message' => "Command denied by allowlist policy (matched deny pattern: {$matchedDeny}).",
                    'cli' => null,
                    'mcpJson' => null,
                    'mcpJsonError' => null,
                ];
            }

            $matchedAllow = $this->firstMatchingPattern($command, $allow);
            $matchedAllowWrite = $this->firstMatchingPattern($command, $allowWritePatterns);

            $allowed = $matchedAllow !== null || ($allowWrite === true && $matchedAllowWrite !== null);

            if ($allowed === false) {
                $message = $matchedAllowWrite !== null
                    ? 'Command requires allowWrite=true.'
                    : 'Command not allowed by default. Add it to .kirby-mcp/mcp.json (cli.allow or cli.allowWrite) to enable.';

                return [
                    'ok' => false,
                    'projectRoot' => $projectRoot,
                    'host' => $host,
                    'command' => $command,
                    'arguments' => $normalizedArgs,
                    'allowWrite' => $allowWrite,
                    'config' => [
                        'path' => $config->path,
                        'error' => $config->error,
                        'allow' => $config->cliAllow(),
                        'allowWrite' => $config->cliAllowWrite(),
                        'deny' => $deny,
                    ],
                    'policy' => [
                        'matchedDeny' => null,
                        'matchedAllow' => $matchedAllow,
                        'matchedAllowWrite' => $matchedAllowWrite,
                    ],
                    'message' => $message,
                    'cli' => null,
                    'mcpJson' => null,
                    'mcpJsonError' => null,
                ];
            }

            $timeoutSeconds = max(5, min(300, $timeoutSeconds));

            $env = [];
            if (is_string($host) && $host !== '') {
                $env['KIRBY_HOST'] = $host;
            }

            $cliResult = (new KirbyCliRunner())->run(
                projectRoot: $projectRoot,
                args: array_merge([$command], $normalizedArgs),
                env: $env,
                timeoutSeconds: $timeoutSeconds,
            );

            $mcpJson = null;
            $mcpJsonError = null;
            try {
                $mcpJson = McpMarkedJsonExtractor::extract($cliResult->stdout);
            } catch (\Throwable $exception) {
                $mcpJsonError = $exception->getMessage();
            }

            return [
                'ok' => true,
                'projectRoot' => $projectRoot,
                'host' => $host,
                'command' => $command,
                'arguments' => $normalizedArgs,
                'allowWrite' => $allowWrite,
                'config' => [
                    'path' => $config->path,
                    'error' => $config->error,
                    'allow' => $config->cliAllow(),
                    'allowWrite' => $config->cliAllowWrite(),
                    'deny' => $deny,
                ],
                'policy' => [
                    'matchedDeny' => null,
                    'matchedAllow' => $matchedAllow,
                    'matchedAllowWrite' => $matchedAllowWrite,
                ],
                'message' => 'Command executed.',
                'cli' => $cliResult->toArray(),
                'mcpJson' => $mcpJson,
                'mcpJsonError' => $mcpJsonError,
            ];
        } catch (\Throwable $exception) {
            McpLog::error($client, [
                'tool' => 'kirby_run_cli',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }

    /**
     * @param array<int, mixed> $arguments
     * @return array<int, string>
     */
    private function normalizeArguments(array $arguments): array
    {
        $out = [];
        foreach ($arguments as $arg) {
            if (!is_string($arg)) {
                continue;
            }

            $arg = trim($arg);
            if ($arg === '') {
                continue;
            }

            $out[] = $arg;
        }

        return $out;
    }

    /**
     * Match a command against allow/deny patterns.
     *
     * Supported pattern: `*` wildcard.
     *
     * @param array<int, string> $patterns
     */
    private function firstMatchingPattern(string $command, array $patterns): ?string
    {
        foreach ($patterns as $pattern) {
            $pattern = trim($pattern);
            if ($pattern === '') {
                continue;
            }

            if ($pattern === $command) {
                return $pattern;
            }

            if (str_contains($pattern, '*') === false) {
                continue;
            }

            $regex = '/^' . str_replace('\\*', '.*', preg_quote($pattern, '/')) . '$/u';
            if (preg_match($regex, $command) === 1) {
                return $pattern;
            }
        }

        return null;
    }

}
