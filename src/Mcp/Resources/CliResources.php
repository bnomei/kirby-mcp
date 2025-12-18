<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Cli\KirbyCliHelpParser;
use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Mcp\Capability\Attribute\McpResource;

final class CliResources
{
    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
    }

    /**
     * @return array{
     *   projectRoot: string,
     *   host: string|null,
     *   cliVersion: string|null,
     *   sections: array<string, array<int, string>>,
     *   commands: array<int, string>,
     *   cli: array{exitCode:int, stdout:string, stderr:string, timedOut:bool}
     * }
     */
    #[McpResource(
        uri: 'kirby://project/cli/commands',
        name: 'project_cli_commands',
        description: 'Kirby CLI command list for this project (parsed from `kirby help`).',
        mimeType: 'application/json',
    )]
    public function commands(): array
    {
        $projectRoot = $this->context->projectRoot();
        $host = $this->context->kirbyHost();

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

        return [
            'projectRoot' => $projectRoot,
            'host' => $host,
            'cliVersion' => $parsed['cliVersion'],
            'sections' => $parsed['sections'],
            'commands' => $parsed['commands'],
            'cli' => $cliResult->toArray(),
        ];
    }
}
