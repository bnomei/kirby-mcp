<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext;
use Bnomei\KirbyMcp\Project\ComposerInspector;
use Bnomei\KirbyMcp\Project\ProjectInfoInspector;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Exception\ResourceReadException;
use Mcp\Schema\Annotations;
use Mcp\Schema\Enum\Role;

final class ProjectResources
{
    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
    ) {
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
    #[McpResource(
        uri: 'kirby://info',
        name: 'info',
        description: 'Project runtime info (PHP + Kirby version via CLI), composer audit, and local environment detection (Herd/DDEV/Docker).',
        mimeType: 'application/json',
        annotations: new Annotations(
            audience: [Role::Assistant],
            priority: 0.5,
        ),
    )]
    #[McpToolIndex(
        whenToUse: 'Use to get quick project context: versions (PHP/Kirby), composer audit, and local environment signals.',
        keywords: [
            'info' => 100,
            'project' => 60,
            'version' => 40,
            'composer' => 30,
            'environment' => 30,
            'roots' => 20,
        ],
    )]
    public function projectInfo(): array
    {
        $projectRoot = $this->context->projectRoot();

        try {
            return (new ProjectInfoInspector())->inspect($projectRoot);
        } catch (\Throwable $exception) {
            throw new ResourceReadException($exception->getMessage());
        }
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
    #[McpResource(
        uri: 'kirby://composer',
        name: 'composer',
        description: 'Composer audit (composer.json/lock): detects test runner and quality tools; returns “how to run” commands.',
        mimeType: 'application/json',
        annotations: new Annotations(
            audience: [Role::Assistant],
            priority: 0.5,
        ),
    )]
    #[McpToolIndex(
        whenToUse: 'Use to inspect composer scripts/tools (tests, phpstan, formatting) and Kirby dependency versions.',
        keywords: [
            'composer' => 100,
            'scripts' => 40,
            'test' => 30,
            'phpstan' => 20,
            'pint' => 20,
            'tools' => 20,
            'audit' => 60,
        ],
    )]
    public function composerAudit(): array
    {
        $projectRoot = $this->context->projectRoot();
        $audit = (new ComposerInspector())->inspect($projectRoot);

        return $audit->toArray();
    }

    /**
     * @return array{projectRoot:string, host:string|null, roots: array<string, string>}
     */
    #[McpResource(
        uri: 'kirby://roots',
        name: 'roots',
        description: 'Kirby roots (kirby()->roots) discovered via Kirby CLI using the configured default host (KIRBY_MCP_HOST/KIRBY_HOST or .kirby-mcp/mcp.json) when present.',
        mimeType: 'application/json',
        annotations: new Annotations(
            audience: [Role::Assistant],
            priority: 0.5,
        ),
    )]
    #[McpToolIndex(
        whenToUse: 'Use to discover Kirby roots (content, site, templates, snippets, blueprints, plugins) as resolved by Kirby at runtime.',
        keywords: [
            'roots' => 100,
            'paths' => 60,
            'content' => 40,
            'site' => 30,
            'templates' => 30,
            'snippets' => 30,
            'blueprints' => 30,
            'plugins' => 30,
            'runtime' => 20,
        ],
    )]
    public function roots(): array
    {
        $runtime = new KirbyRuntimeContext($this->context);
        $projectRoot = $runtime->projectRoot();
        $host = $runtime->host();
        $roots = $runtime->roots();

        return [
            'projectRoot' => $projectRoot,
            'host' => $host,
            'roots' => $roots->toArray(),
        ];
    }
}
