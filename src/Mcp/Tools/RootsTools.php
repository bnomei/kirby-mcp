<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\McpLog;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext;
use Mcp\Exception\ToolCallException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;

final class RootsTools
{
    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
    }

    /**
     * Get Kirby’s resolved roots (directories/paths) for this project via the Kirby CLI (`kirby roots`).
     *
     * Roots are configurable in Kirby; use this tool before assuming folder locations for `site/`, `content/`, `media/`, etc.
     *
     * @return array{
     *   projectRoot: string,
     *   host: string|null,
     *   roots: array<string, string>,
     *   commandsRoot: string|null,
     *   cli: array{exitCode:int, stdout:string, stderr:string, timedOut:bool}
     * }
     */
    #[McpToolIndex(
        whenToUse: 'Call before searching/editing Kirby project files to discover the resolved folder roots (custom folder setups can move site/content/media/etc).',
        keywords: [
            'roots' => 100,
            'paths' => 70,
            'folders' => 70,
            'folder' => 70,
            'directories' => 60,
            'site' => 40,
            'content' => 40,
            'media' => 40,
            'cache' => 30,
            'logs' => 20,
            'commands' => 30,
            'custom' => 30,
        ],
    )]
    #[McpTool(
        name: 'kirby_roots',
        description: 'Return Kirby’s resolved folder roots (kirby()->roots) via `kirby roots`. Uses configured default host (KIRBY_MCP_HOST/KIRBY_HOST or .kirby-mcp/mcp.json) when present. Resource: `kirby://roots`.',
        annotations: new ToolAnnotations(
            title: 'Kirby Roots',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function roots(?ClientGateway $client = null): array
    {
        try {
            $runtime = new KirbyRuntimeContext($this->context);
            $projectRoot = $runtime->projectRoot();
            $host = $runtime->host();

            $inspection = $runtime->rootsInspection();
            $roots = $inspection->roots;
            $cliResult = $inspection->cliResult;

            return [
                'projectRoot' => $projectRoot,
                'host' => $host,
                'roots' => $roots->toArray(),
                'commandsRoot' => $roots->commandsRoot(),
                'cli' => $cliResult->toArray(),
            ];
        } catch (\Throwable $exception) {
            McpLog::error($client, [
                'tool' => 'kirby_roots',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }
}
