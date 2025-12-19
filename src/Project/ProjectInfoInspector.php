<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Project;

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;

final class ProjectInfoInspector
{
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
    public function inspect(string $projectRoot): array
    {
        $composerAudit = (new ComposerInspector())->inspect($projectRoot);
        if (!isset($composerAudit->composerJson['require']['getkirby/cms'])) {
            throw new \RuntimeException('This MCP server supports composer-based Kirby installs only (missing getkirby/cms).');
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
}
