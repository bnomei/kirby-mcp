<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Project;

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;

final class KirbyRootsInspector
{
    public function inspect(string $projectRoot, ?string $host = null): KirbyRoots
    {
        $env = [];
        if (is_string($host) && $host !== '') {
            $env['KIRBY_HOST'] = $host;
        }

        $result = (new KirbyCliRunner())->run(
            projectRoot: $projectRoot,
            args: ['roots'],
            env: $env,
            timeoutSeconds: 30,
        );

        return KirbyRoots::fromCliOutput($result->stdout);
    }
}
