<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Completion;

use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Project\KirbyRootsInspector;
use Mcp\Capability\Completion\ProviderInterface;

final class KirbyHostCompletionProvider implements ProviderInterface
{
    public function getCompletions(string $currentValue): array
    {
        $currentValue = trim($currentValue);

        $projectRoot = (new ProjectContext())->projectRoot();
        $roots = (new KirbyRootsInspector())->inspect($projectRoot);
        $configRoot = $roots->get('config') ?? ($projectRoot . '/site/config');

        $hosts = [];
        foreach (glob(rtrim($configRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'config.*.php') ?: [] as $file) {
            $base = basename($file);
            if ($base === 'config.php' || $base === 'config.cli.php') {
                continue;
            }

            $host = preg_replace('/^config\\./', '', $base);
            $host = preg_replace('/\\.php$/', '', (string) $host);
            $host = trim((string) $host);
            if ($host === '') {
                continue;
            }

            if ($currentValue !== '' && !str_contains(strtolower($host), strtolower($currentValue))) {
                continue;
            }

            $hosts[] = $host;
        }

        $hosts = array_values(array_unique($hosts));
        sort($hosts);

        return $hosts;
    }
}
