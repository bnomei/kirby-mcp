<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Project;

use Bnomei\KirbyMcp\Support\Json;

final class ComposerInspector
{
    /** @var array<string, array{composerJsonMtime:int, composerLockMtime:int, audit: ComposerAudit}> */
    private static array $cache = [];

    public static function clearCache(): int
    {
        $count = count(self::$cache);
        self::$cache = [];

        return $count;
    }

    public function inspect(string $projectRoot): ComposerAudit
    {
        $composerJsonPath = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'composer.json';
        $composerLockPath = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'composer.lock';

        $composerJsonMtime = filemtime($composerJsonPath);
        $composerJsonMtime = is_int($composerJsonMtime) ? $composerJsonMtime : 0;

        $composerLockMtime = is_file($composerLockPath) ? filemtime($composerLockPath) : false;
        $composerLockMtime = is_int($composerLockMtime) ? $composerLockMtime : 0;

        $cacheKey = rtrim($projectRoot, DIRECTORY_SEPARATOR);
        $cached = self::$cache[$cacheKey] ?? null;
        if (
            is_array($cached)
            && ($cached['composerJsonMtime'] ?? null) === $composerJsonMtime
            && ($cached['composerLockMtime'] ?? null) === $composerLockMtime
            && ($cached['audit'] ?? null) instanceof ComposerAudit
        ) {
            return $cached['audit'];
        }

        $composerJson = Json::decodeFile($composerJsonPath);
        $composerLock = is_file($composerLockPath) ? Json::decodeFile($composerLockPath) : null;

        /** @var array<string, mixed> $scripts */
        $scripts = [];
        if (isset($composerJson['scripts']) && is_array($composerJson['scripts'])) {
            /** @var array<string, mixed> $scripts */
            $scripts = $composerJson['scripts'];
        }

        $tools = $this->detectTools($composerJson, $scripts);

        $audit = new ComposerAudit(
            projectRoot: $projectRoot,
            composerJson: $composerJson,
            composerLock: $composerLock,
            scripts: $scripts,
            tools: $tools,
        );

        self::$cache[$cacheKey] = [
            'composerJsonMtime' => $composerJsonMtime,
            'composerLockMtime' => $composerLockMtime,
            'audit' => $audit,
        ];

        return $audit;
    }

    /**
     * @param array<mixed> $composerJson
     * @param array<string, mixed> $scripts
     * @return array<string, array{tool: string, present: bool, via?: 'require'|'require-dev'|'script'|'bin', run?: string}>
     */
    private function detectTools(array $composerJson, array $scripts): array
    {
        /** @var array<string, string> $require */
        $require = [];
        if (isset($composerJson['require']) && is_array($composerJson['require'])) {
            /** @var array<string, string> $require */
            $require = $composerJson['require'];
        }

        /** @var array<string, string> $requireDev */
        $requireDev = [];
        if (isset($composerJson['require-dev']) && is_array($composerJson['require-dev'])) {
            /** @var array<string, string> $requireDev */
            $requireDev = $composerJson['require-dev'];
        }

        $knownTools = [
            'pest' => [
                'packages' => ['pestphp/pest'],
                'bins' => ['pest'],
                'scripts' => ['test', 'pest'],
            ],
            'phpunit' => [
                'packages' => ['phpunit/phpunit'],
                'bins' => ['phpunit'],
                'scripts' => ['test', 'phpunit'],
            ],
            'phpstan' => [
                'packages' => ['phpstan/phpstan', 'larastan/larastan', 'nunomaduro/larastan'],
                'bins' => ['phpstan'],
                'scripts' => ['analyse', 'phpstan', 'stan'],
            ],
            'psalm' => [
                'packages' => ['vimeo/psalm'],
                'bins' => ['psalm'],
                'scripts' => ['psalm'],
            ],
            'php-cs-fixer' => [
                'packages' => ['friendsofphp/php-cs-fixer'],
                'bins' => ['php-cs-fixer'],
                'scripts' => ['cs', 'fix', 'php-cs-fixer'],
            ],
            'phpcs' => [
                'packages' => ['squizlabs/php_codesniffer', 'phpcsstandards/phpcsutils'],
                'bins' => ['phpcs', 'phpcbf'],
                'scripts' => ['phpcs', 'lint'],
            ],
            'pint' => [
                'packages' => ['laravel/pint'],
                'bins' => ['pint'],
                'scripts' => ['pint'],
            ],
            'phpactor' => [
                'packages' => ['phpactor/phpactor'],
                'bins' => ['phpactor'],
                'scripts' => ['phpactor'],
            ],
            'ray' => [
                'packages' => ['spatie/ray'],
                'bins' => [],
                'scripts' => [],
            ],
        ];

        $tools = [];

        foreach ($knownTools as $tool => $hints) {
            $present = false;
            $via = null;
            $run = null;

            foreach ($hints['packages'] as $package) {
                if (array_key_exists($package, $require)) {
                    $present = true;
                    $via = 'require';
                    break;
                }
                if (array_key_exists($package, $requireDev)) {
                    $present = true;
                    $via = 'require-dev';
                    break;
                }
            }

            if ($present === false) {
                foreach ($hints['scripts'] as $scriptName) {
                    if (array_key_exists($scriptName, $scripts)) {
                        $present = true;
                        $via = 'script';
                        $run = "composer {$scriptName}";
                        break;
                    }
                }
            }

            if ($present === true && $run === null && isset($hints['bins'][0])) {
                $run = 'vendor/bin/' . $hints['bins'][0];
                if ($via === null) {
                    $via = 'bin';
                }
            }

            $tools[$tool] = array_filter([
                'tool' => $tool,
                'present' => $present,
                'via' => $via,
                'run' => $run,
            ], static fn ($value) => $value !== null);
        }

        return $tools;
    }
}
