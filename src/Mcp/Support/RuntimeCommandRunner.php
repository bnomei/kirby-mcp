<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Support;

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Cli\McpMarkedJsonExtractor;

final class RuntimeCommandRunner
{
    public const NEEDS_RUNTIME_INSTALL_MESSAGE = 'Kirby MCP runtime CLI commands are not installed in this project. Run kirby_runtime_install first.';
    public const DEFAULT_PARSE_ERROR = 'Unable to parse JSON output from Kirby CLI command.';

    public function __construct(
        private readonly KirbyRuntimeContext $runtime = new KirbyRuntimeContext(),
        private readonly KirbyCliRunner $cliRunner = new KirbyCliRunner(),
    ) {
    }

    /**
     * @param array<int, string> $args
     */
    public function runMarkedJson(
        string $expectedCommandRelativePath,
        array $args,
        int $timeoutSeconds = 60,
    ): RuntimeCommandResult {
        $projectRoot = $this->runtime->projectRoot();
        $host = $this->runtime->host();
        $commandsRoot = $this->runtime->commandsRoot();

        $expectedCommandFile = $this->runtime->commandFile($expectedCommandRelativePath);
        if (!is_file($expectedCommandFile)) {
            return new RuntimeCommandResult(
                projectRoot: $projectRoot,
                host: $host,
                commandsRoot: $commandsRoot,
                expectedCommandFile: $expectedCommandFile,
                installed: false,
            );
        }

        $timeoutSeconds = max(5, min(300, $timeoutSeconds));

        $cliResult = $this->cliRunner->run(
            projectRoot: $projectRoot,
            args: $args,
            env: $this->runtime->env(),
            timeoutSeconds: $timeoutSeconds,
        );

        $payload = null;
        $parseError = null;

        try {
            $payload = McpMarkedJsonExtractor::extract($cliResult->stdout);
        } catch (\Throwable $exception) {
            $payload = null;
            $parseError = $exception->getMessage();
        }

        if (!is_array($payload) && (!is_string($parseError) || $parseError === '')) {
            $parseError = self::DEFAULT_PARSE_ERROR;
        }

        return new RuntimeCommandResult(
            projectRoot: $projectRoot,
            host: $host,
            commandsRoot: $commandsRoot,
            expectedCommandFile: $expectedCommandFile,
            installed: true,
            cliResult: $cliResult,
            payload: is_array($payload) ? $payload : null,
            parseError: is_array($payload) ? null : $parseError,
        );
    }
}
