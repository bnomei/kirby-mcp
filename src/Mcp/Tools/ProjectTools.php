<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\McpLog;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Project\ComposerInspector;
use Bnomei\KirbyMcp\Project\ProjectInfoInspector;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;

final class ProjectTools
{
    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
    }

    /**
     * @return array{
     *   projectRoot: string,
     *   composerJson: array<mixed>,
     *   composerLock: array<mixed>|null,
     *   scripts: array<string, mixed>,
     *   tools: array<string, mixed>
     * }
     */
    #[McpToolIndex(
        whenToUse: 'Call before generating code changes to learn how this project runs tests, static analysis, and formatting (composer scripts + installed tooling).',
        keywords: [
            'composer' => 100,
            'dependencies' => 50,
            'scripts' => 70,
            'audit' => 60,
            'test' => 60,
            'tests' => 60,
            'phpunit' => 90,
            'pest' => 90,
            'phpstan' => 90,
            'larastan' => 90,
            'psalm' => 90,
            'phpcs' => 70,
            'php-cs-fixer' => 70,
            'pint' => 70,
            'phpactor' => 50,
            'lint' => 60,
            'static' => 50,
            'analysis' => 50,
            'quality' => 50,
            'format' => 40,
            'formatter' => 40,
            'ci' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_composer_audit',
        description: 'Parse composer.json/lock to detect Kirby version, scripts, test runner, and quality tools (phpstan/larastan/psalm/pint/phpcs/php-cs-fixer). Returns “how to run” commands. Resource: `kirby://composer`.',
        annotations: new ToolAnnotations(
            title: 'Composer Audit',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function composerAudit(?ClientGateway $client = null): array
    {
        try {
            $projectRoot = $this->context->projectRoot();
            $audit = (new ComposerInspector())->inspect($projectRoot);

            return $audit->toArray();
        } catch (\Throwable $exception) {
            McpLog::error($client, [
                'tool' => 'kirby_composer_audit',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }

    /**
     * @return array{
     *   projectRoot: string,
     *   phpVersion: string,
     *   kirbyVersion: string,
     *   environment: array{projectRoot:string, localRunner:string, signals: array<string, string>},
     *   composer: array{
     *     projectRoot: string,
     *     composerJson: array<mixed>,
     *     composerLock: array<mixed>|null,
     *     scripts: array<string, mixed>,
     *     tools: array<string, mixed>
     *   }
     * }
     */
    #[McpToolIndex(
        whenToUse: 'Use when you need a quick overview of the project (PHP + Kirby versions, composer audit, and local dev runner detection like Herd/DDEV/Docker).',
        keywords: [
            'project' => 80,
            'info' => 80,
            'kirby' => 40,
            'php' => 30,
            'version' => 40,
            'environment' => 70,
            'runner' => 50,
            'local' => 40,
            'herd' => 60,
            'ddev' => 60,
            'docker' => 60,
        ],
    )]
    #[McpTool(
        name: 'kirby_info',
        description: 'Return project runtime info (PHP + Kirby version via Kirby CLI), composer audit, and local environment detection (Herd/DDEV/Docker). Resource: `kirby://info`.',
        annotations: new ToolAnnotations(
            title: 'Project Info',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function projectInfo(?ClientGateway $client = null): array
    {
        try {
            $projectRoot = $this->context->projectRoot();
            return (new ProjectInfoInspector())->inspect($projectRoot);
        } catch (ToolCallException $exception) {
            McpLog::error($client, [
                'tool' => 'kirby_info',
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        } catch (\Throwable $exception) {
            McpLog::error($client, [
                'tool' => 'kirby_info',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }

    /**
     * @return array{exitCode:int, stdout:string, stderr:string, timedOut:bool}
     */
    #[McpToolIndex(
        whenToUse: 'Use when you just need to verify Kirby boots via the CLI and confirm the installed Kirby version.',
        keywords: [
            'cli' => 70,
            'version' => 90,
            'kirby' => 40,
            'check' => 30,
            'verify' => 30,
            'boot' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_cli_version',
        description: 'Run `kirby version` (Kirby CLI) and return stdout/stderr/exit code. Useful to confirm Kirby boots and which version is installed.',
        annotations: new ToolAnnotations(
            title: 'Kirby Version (CLI)',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function kirbyCliVersion(?ClientGateway $client = null): array
    {
        try {
            $projectRoot = $this->context->projectRoot();

            $result = (new KirbyCliRunner())->run(
                projectRoot: $projectRoot,
                args: ['version'],
                timeoutSeconds: 30,
            );

            return $result->toArray();
        } catch (\Throwable $exception) {
            McpLog::error($client, [
                'tool' => 'kirby_cli_version',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }
}
