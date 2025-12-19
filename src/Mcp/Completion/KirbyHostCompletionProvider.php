<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Completion;

use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Bnomei\KirbyMcp\Support\StaticCache;
use Mcp\Capability\Completion\ProviderInterface;

final class KirbyHostCompletionProvider implements ProviderInterface
{
    public function getCompletions(string $currentValue): array
    {
        $currentValue = trim($currentValue);

        $runtime = new KirbyRuntimeContext(new ProjectContext());
        $projectRoot = $runtime->projectRoot();
        $configRoot = $runtime->root(
            key: 'config',
            fallback: rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'config',
        );

        $configRoot = is_string($configRoot) ? rtrim($configRoot, DIRECTORY_SEPARATOR) : '';
        if ($configRoot === '') {
            return [];
        }

        $ttlSeconds = KirbyMcpConfig::load($projectRoot)->cacheTtlSeconds();

        $dirMtime = is_dir($configRoot) ? filemtime($configRoot) : false;
        $dirMtime = is_int($dirMtime) ? $dirMtime : 0;

        $cacheKey = 'completion:hosts:' . sha1($projectRoot . '|' . $configRoot . '|' . $dirMtime);

        $hosts = null;
        if ($ttlSeconds > 0) {
            $cached = StaticCache::get($cacheKey);
            if (is_array($cached)) {
                $hosts = array_values(array_filter($cached, static fn (mixed $host): bool => is_string($host) && $host !== ''));
            }
        }

        if (!is_array($hosts)) {
            $hosts = [];
            foreach (glob($configRoot . DIRECTORY_SEPARATOR . 'config.*.php') ?: [] as $file) {
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

                $hosts[] = $host;
            }

            $hosts = array_values(array_unique($hosts));
            sort($hosts);

            if ($ttlSeconds > 0) {
                StaticCache::set($cacheKey, $hosts, $ttlSeconds);
            }
        }

        if ($currentValue !== '') {
            $query = strtolower($currentValue);
            $hosts = array_values(array_filter($hosts, static fn (string $host): bool => str_contains(strtolower($host), $query)));
        }

        return $hosts;
    }
}
