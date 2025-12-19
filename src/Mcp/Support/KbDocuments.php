<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Support;

use Bnomei\KirbyMcp\Support\StaticCache;

final class KbDocuments
{
    public const CACHE_KEY = 'kb:documents:v1';

    public static function projectRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    public static function kbRoot(): string
    {
        return self::projectRoot() . DIRECTORY_SEPARATOR . 'kb';
    }

    /**
     * Load KB markdown files into memory once per MCP process.
     *
     * Excludes `PLAN.md` and `AGENTS.md` anywhere under `kb/`.
     *
     * @return array<string, string> Map: relative file path => markdown contents
     */
    public static function all(): array
    {
        /** @var array<string, string>|null $cached */
        $cached = StaticCache::get(self::CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $kbRoot = self::kbRoot();
        if (!is_dir($kbRoot)) {
            throw new \RuntimeException('Knowledge base directory not found: ' . $kbRoot);
        }

        $projectRoot = self::projectRoot();

        /** @var array<string, string> $documents */
        $documents = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($kbRoot, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'md') {
                continue;
            }

            $name = strtolower($file->getFilename());
            if ($name === 'plan.md' || $name === 'agents.md') {
                continue;
            }

            $path = $file->getPathname();
            $contents = file_get_contents($path);
            if (!is_string($contents) || $contents === '') {
                continue;
            }

            $relative = ltrim(substr($path, strlen($projectRoot)), DIRECTORY_SEPARATOR);
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);

            $documents[$relative] = $contents;
        }

        ksort($documents);

        StaticCache::set(self::CACHE_KEY, $documents);

        return $documents;
    }
}
