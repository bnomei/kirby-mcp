<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Project;

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;

final class KirbyRootsInspector
{
    public function inspect(string $projectRoot, ?string $host = null): KirbyRoots
    {
        return $this->inspectWithCli($projectRoot, $host)->roots;
    }

    public function inspectWithCli(string $projectRoot, ?string $host = null): KirbyRootsInspectionResult
    {
        $env = [];
        if (is_string($host) && $host !== '') {
            $env['KIRBY_HOST'] = $host;
        }

        $cliResult = (new KirbyCliRunner())->run(
            projectRoot: $projectRoot,
            args: ['roots'],
            env: $env,
            timeoutSeconds: 30,
        );

        // Only attempt to parse valid CLI output; if command failed, return empty roots
        $roots = $cliResult->exitCode === 0 && $cliResult->stdout !== ''
            ? KirbyRoots::fromCliOutput($cliResult->stdout)
            : new KirbyRoots([]);

        return new KirbyRootsInspectionResult(
            roots: $roots,
            cliResult: $cliResult,
        );
    }
}
