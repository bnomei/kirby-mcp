<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Bnomei\KirbyMcp\Project\ProjectRootFinder;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;

final class ProjectContext
{
    public const ENV_PROJECT_ROOT = 'KIRBY_MCP_PROJECT_ROOT';
    public const ENV_HOST = 'KIRBY_MCP_HOST';

    public function projectRoot(): string
    {
        $root = getenv(self::ENV_PROJECT_ROOT);
        if (is_string($root) && $root !== '') {
            return $root;
        }

        $finder = new ProjectRootFinder();
        $detected = $finder->findKirbyProjectRoot();
        if (is_string($detected) && $detected !== '') {
            return $detected;
        }

        $cwd = getcwd();
        if (is_string($cwd)) {
            return $cwd;
        }

        throw new \RuntimeException('Unable to determine project root; set ' . self::ENV_PROJECT_ROOT . '.');
    }

    public function kirbyHost(): ?string
    {
        $host = getenv(self::ENV_HOST);
        if (is_string($host) && trim($host) !== '') {
            return trim($host);
        }

        $host = getenv('KIRBY_HOST');
        if (is_string($host) && trim($host) !== '') {
            return trim($host);
        }

        $config = KirbyMcpConfig::load($this->projectRoot());

        return $config->kirbyHost();
    }
}
