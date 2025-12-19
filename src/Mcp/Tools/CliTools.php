<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Cli\McpMarkedJsonExtractor;
use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\McpLog;
use Bnomei\KirbyMcp\Mcp\Policies\KirbyCliAllowlistPolicy;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommands;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;

final class CliTools
{
    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
    }

    /**
     * Run a Kirby CLI command (raw stdout/stderr).
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
     *   success: bool|null,
     *   exitCode: int|null,
     *   stdout: string|null,
     *   stderr: string|null,
     *   timedOut: bool|null,
     *   mcpJson: array<mixed>|null,
     *   mcpJsonError: string|null
     * }
     */
    #[McpToolIndex(
        whenToUse: 'Use to execute a Kirby CLI command and capture its raw output (stdout/stderr). Discover commands via `kirby://commands` and inspect flags/usage via `kirby://cli/command/{command}`.',
        keywords: [
            'cli' => 80,
            'run' => 100,
            'command' => 100,
            'execute' => 80,
            'stdout' => 60,
            'stderr' => 60,
            'output' => 60,
            'raw' => 40,
            'help' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_run_cli_command',
        description: 'Run a Kirby CLI command and return raw stdout/stderr + exit code. Commands are guarded by an allowlist (built-in + optional .kirby-mcp/mcp.json); set allowWrite=true for write-capable commands (e.g. make:* or mcp:*). Prefer dedicated MCP resources/tools for common tasks (e.g. `kirby://roots`, `kirby://commands`, `kirby://cli/command/{command}`, `kirby://config/{option}`, `kirby://blueprint/{encodedId}`, `kirby://page/content/{encodedIdOrUuid}`).',
        annotations: new ToolAnnotations(
            title: 'Run Kirby CLI Command',
            readOnlyHint: false,
            destructiveHint: true,
            openWorldHint: false,
        ),
    )]
    public function runCliCommand(
        string $command,
        array $arguments = [],
        bool $allowWrite = false,
        int $timeoutSeconds = 60,
        ?ClientGateway $client = null,
    ): array {
        $result = $this->runCliInternal(
            command: $command,
            arguments: $arguments,
            allowWrite: $allowWrite,
            timeoutSeconds: $timeoutSeconds,
            client: $client,
        );

        if (($result['ok'] ?? false) !== true) {
            return [
                'ok' => false,
                'projectRoot' => $result['projectRoot'] ?? $this->context->projectRoot(),
                'host' => $result['host'] ?? $this->context->kirbyHost(),
                'command' => $result['command'] ?? $command,
                'arguments' => $result['arguments'] ?? [],
                'allowWrite' => (bool) ($result['allowWrite'] ?? $allowWrite),
                'config' => is_array($result['config'] ?? null) ? $result['config'] : [
                    'path' => null,
                    'error' => null,
                    'allow' => [],
                    'allowWrite' => [],
                    'deny' => [],
                ],
                'policy' => is_array($result['policy'] ?? null) ? $result['policy'] : [
                    'matchedDeny' => null,
                    'matchedAllow' => null,
                    'matchedAllowWrite' => null,
                ],
                'message' => is_string($result['message'] ?? null) ? $result['message'] : 'Command not executed.',
                'success' => null,
                'exitCode' => null,
                'stdout' => null,
                'stderr' => null,
                'timedOut' => null,
                'mcpJson' => null,
                'mcpJsonError' => null,
            ];
        }

        $cli = $result['cli'] ?? null;
        $exitCode = is_array($cli) ? ($cli['exitCode'] ?? null) : null;
        $timedOut = is_array($cli) ? ($cli['timedOut'] ?? null) : null;
        $stdout = is_array($cli) ? ($cli['stdout'] ?? null) : null;
        $stderr = is_array($cli) ? ($cli['stderr'] ?? null) : null;

        $success = null;
        if (is_int($exitCode) && is_bool($timedOut)) {
            $success = ($exitCode === 0) && ($timedOut === false);
        }

        return [
            'ok' => true,
            'projectRoot' => $result['projectRoot'],
            'host' => $result['host'],
            'command' => $result['command'],
            'arguments' => $result['arguments'],
            'allowWrite' => (bool) ($result['allowWrite'] ?? $allowWrite),
            'config' => $result['config'],
            'policy' => $result['policy'],
            'message' => is_string($result['message'] ?? null) ? $result['message'] : 'Command executed.',
            'success' => $success,
            'exitCode' => is_int($exitCode) ? $exitCode : null,
            'stdout' => is_string($stdout) ? $stdout : null,
            'stderr' => is_string($stderr) ? $stderr : null,
            'timedOut' => is_bool($timedOut) ? $timedOut : null,
            'mcpJson' => is_array($result['mcpJson'] ?? null) ? $result['mcpJson'] : null,
            'mcpJsonError' => is_string($result['mcpJsonError'] ?? null) ? $result['mcpJsonError'] : null,
        ];
    }

    /**
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
    private function runCliInternal(
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
            $decision = (new KirbyCliAllowlistPolicy($config))->evaluate($command, $allowWrite);

            if ($decision->matchedDeny !== null) {
                $message = "Command denied by allowlist policy (matched deny pattern: {$decision->matchedDeny}).";
                $hint = $this->hintForCommand($command);
                if (is_string($hint) && $hint !== '') {
                    $message .= ' ' . $hint;
                }

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
                    'policy' => $decision->toArray(),
                    'message' => $message,
                    'cli' => null,
                    'mcpJson' => null,
                    'mcpJsonError' => null,
                ];
            }

            if ($decision->allowed === false) {
                $message = $decision->requiresAllowWrite()
                    ? 'Command requires allowWrite=true.'
                    : 'Command not allowed by default. Add it to .kirby-mcp/mcp.json (cli.allow or cli.allowWrite) to enable.';

                $hint = $this->hintForCommand($command);
                if (is_string($hint) && $hint !== '') {
                    $message .= ' ' . $hint;
                }

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
                    'policy' => $decision->toArray(),
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

            $message = 'Command executed.';
            $hint = $this->hintForCommand($command);
            if (is_string($hint) && $hint !== '') {
                $message .= ' ' . $hint;
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
                'policy' => $decision->toArray(),
                'message' => $message,
                'cli' => $cliResult->toArray(),
                'mcpJson' => $mcpJson,
                'mcpJsonError' => $mcpJsonError,
            ];
        } catch (\Throwable $exception) {
            McpLog::error($client, [
                'tool' => 'kirby_run_cli_command',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }

    private function hintForCommand(string $command): ?string
    {
        return match ($command) {
            'roots' => 'Prefer resource `kirby://roots` (or tool `kirby_roots`).',
            'help', RuntimeCommands::CLI_COMMANDS => 'Prefer resources `kirby://commands` and `kirby://cli/command/{command}`.',
            RuntimeCommands::CONFIG_GET => 'Prefer resource `kirby://config/{option}` (requires kirby_runtime_install) for reading config options.',
            RuntimeCommands::BLUEPRINT => 'Prefer resource `kirby://blueprint/{encodedId}` (or tool `kirby_blueprint_read`).',
            RuntimeCommands::PAGE_CONTENT => 'Prefer resource `kirby://page/content/{encodedIdOrUuid}` (or tool `kirby_read_page_content`).',
            default => null,
        };
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

}
