<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Project\ComposerInspector;
use Bnomei\KirbyMcp\Project\EnvironmentDetector;
use Bnomei\KirbyMcp\Project\KirbyRootsInspector;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Exception\ResourceReadException;

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
        uri: 'kirby://project/info',
        name: 'project_info',
        description: 'Project runtime info (PHP + Kirby version via CLI), composer audit, and local environment detection (Herd/DDEV/Docker).',
        mimeType: 'application/json',
    )]
    public function projectInfo(): array
    {
        $projectRoot = $this->context->projectRoot();

        $composerAudit = (new ComposerInspector())->inspect($projectRoot);
        if (!isset($composerAudit->composerJson['require']['getkirby/cms'])) {
            throw new ResourceReadException('This MCP server supports composer-based Kirby installs only (missing getkirby/cms).');
        }

        $environment = (new EnvironmentDetector())->detect($projectRoot);

        $cliResult = (new KirbyCliRunner())->run(
            projectRoot: $projectRoot,
            args: ['version'],
            timeoutSeconds: 30,
        );

        return [
            'projectRoot' => $projectRoot,
            'phpVersion' => PHP_VERSION,
            'kirbyVersion' => trim($cliResult->stdout),
            'environment' => $environment->toArray(),
            'composer' => $composerAudit->toArray(),
        ];
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
        uri: 'kirby://project/composer',
        name: 'project_composer',
        description: 'Composer audit (composer.json/lock): detects test runner and quality tools; returns “how to run” commands.',
        mimeType: 'application/json',
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
        uri: 'kirby://project/roots',
        name: 'project_roots',
        description: 'Kirby roots (kirby()->roots) discovered via Kirby CLI using the configured default host (KIRBY_MCP_HOST/KIRBY_HOST or .kirby-mcp/mcp.json) when present.',
        mimeType: 'application/json',
    )]
    public function roots(): array
    {
        $projectRoot = $this->context->projectRoot();
        $host = $this->context->kirbyHost();
        $roots = (new KirbyRootsInspector())->inspect($projectRoot, $host);

        return [
            'projectRoot' => $projectRoot,
            'host' => $host,
            'roots' => $roots->toArray(),
        ];
    }
}
