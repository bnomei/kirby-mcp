<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Project;

final class ProjectRootFinder
{
    public function findKirbyProjectRoot(?string $startDir = null): ?string
    {
        $dir = $startDir;
        if ($dir === null || $dir === '') {
            $cwd = getcwd();
            $dir = is_string($cwd) ? $cwd : null;
        }

        if ($dir === null) {
            return null;
        }

        $current = rtrim($dir, DIRECTORY_SEPARATOR);
        while ($current !== '') {
            if ($this->isKirbyComposerProject($current)) {
                return $current;
            }

            $parent = dirname($current);
            if ($parent === $current) {
                break;
            }

            $current = $parent;
        }

        return null;
    }

    private function isKirbyComposerProject(string $dir): bool
    {
        $composerJsonPath = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'composer.json';
        if (!is_file($composerJsonPath)) {
            return false;
        }

        $contents = file_get_contents($composerJsonPath);
        if (!is_string($contents) || $contents === '') {
            return false;
        }

        try {
            /** @var array<string, mixed> $json */
            $json = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return false;
        }

        $require = $json['require'] ?? null;
        if (!is_array($require)) {
            return false;
        }

        return array_key_exists('getkirby/cms', $require);
    }
}
