<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Support;

use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Project\KirbyRoots;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Bnomei\KirbyMcp\Project\KirbyRootsInspector;
use Bnomei\KirbyMcp\Project\KirbyRootsInspectionResult;

final class KirbyRuntimeContext implements RuntimeContextInterface
{
    /** @var array<string, KirbyRootsInspectionCacheEntry> */
    private static array $rootsInspectionCache = [];

    public function __construct(
        private readonly ProjectContext $context = new ProjectContext(),
        /** @var array<string, string> */
        private readonly array $envOverrides = [],
    ) {
    }

    public function projectRoot(): string
    {
        return $this->context->projectRoot();
    }

    public function host(): ?string
    {
        return $this->context->kirbyHost();
    }

    /**
     * @return array<string, string>
     */
    public function env(): array
    {
        $env = [];

        $host = $this->host();
        if (is_string($host) && trim($host) !== '') {
            $env['KIRBY_HOST'] = trim($host);
        }

        foreach ($this->envOverrides as $key => $value) {
            if (!is_string($key) || $key === '' || !is_string($value)) {
                continue;
            }

            $env[$key] = $value;
        }

        return $env;
    }

    public function roots(): KirbyRoots
    {
        return $this->rootsInspection()->roots;
    }

    public function rootsInspection(): KirbyRootsInspectionResult
    {
        $projectRoot = $this->projectRoot();
        $host = $this->host();
        $ttlSeconds = KirbyMcpConfig::load($projectRoot)->cacheTtlSeconds();

        $cacheKey = rtrim($projectRoot, DIRECTORY_SEPARATOR) . '|' . trim((string) $host);
        $entry = self::$rootsInspectionCache[$cacheKey] ?? null;

        if ($ttlSeconds > 0 && $entry instanceof KirbyRootsInspectionCacheEntry) {
            $age = time() - $entry->inspectedAt;

            if ($age < $ttlSeconds) {
                if (!is_string($entry->indexPhpPath) || $entry->indexPhpPath === '') {
                    return $entry->inspection;
                }

                if (!is_file($entry->indexPhpPath)) {
                    if ($entry->indexPhpMtime === null) {
                        return $entry->inspection;
                    }
                } else {
                    $mtime = filemtime($entry->indexPhpPath);
                    if (!is_int($mtime)) {
                        return $entry->inspection;
                    }

                    if ($mtime === $entry->indexPhpMtime) {
                        return $entry->inspection;
                    }
                }
            }
        }

        $inspection = (new KirbyRootsInspector())->inspectWithCli($projectRoot, $host);

        $indexPhpPath = null;
        $indexPhpMtime = null;

        $indexRoot = $inspection->roots->get('index');
        if (is_string($indexRoot) && trim($indexRoot) !== '') {
            $indexPhpPath = rtrim($indexRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php';
            $mtime = is_file($indexPhpPath) ? filemtime($indexPhpPath) : false;
            $indexPhpMtime = is_int($mtime) ? $mtime : null;
        }

        self::$rootsInspectionCache[$cacheKey] = new KirbyRootsInspectionCacheEntry(
            inspection: $inspection,
            inspectedAt: time(),
            indexPhpPath: $indexPhpPath,
            indexPhpMtime: $indexPhpMtime,
        );

        return $inspection;
    }

    public static function clearRootsCache(): int
    {
        $count = count(self::$rootsInspectionCache);
        self::$rootsInspectionCache = [];

        return $count;
    }

    public function root(string $key, ?string $fallback = null): ?string
    {
        $value = $this->roots()->get($key);
        if (is_string($value) && $value !== '') {
            return rtrim($value, DIRECTORY_SEPARATOR);
        }

        return is_string($fallback) && $fallback !== '' ? rtrim($fallback, DIRECTORY_SEPARATOR) : null;
    }

    public function commandsRoot(): string
    {
        return $this->roots()->commandsRoot()
            ?? rtrim($this->projectRoot(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'commands';
    }

    public function commandFile(string $relativePath): string
    {
        $relativePath = ltrim($relativePath, '/');
        $relativePath = str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        return rtrim($this->commandsRoot(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $relativePath;
    }
}
