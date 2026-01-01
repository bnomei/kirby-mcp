<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Project;

use Bnomei\KirbyMcp\Support\Json;

final class ComposerInspector
{
    /** @var array<string, array{composerJsonMtime:int|null, audit: ComposerAudit}> */
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

        $composerJsonMtime = is_file($composerJsonPath) ? filemtime($composerJsonPath) : false;
        $composerJsonMtime = is_int($composerJsonMtime) ? $composerJsonMtime : null;

        $cacheKey = rtrim($projectRoot, DIRECTORY_SEPARATOR);
        $cached = self::$cache[$cacheKey] ?? null;
        if (
            is_array($cached)
            && ($cached['composerJsonMtime'] ?? null) === $composerJsonMtime
            && ($cached['audit'] ?? null) instanceof ComposerAudit
        ) {
            return $cached['audit'];
        }

        $composerJson = Json::decodeFile($composerJsonPath);

        /** @var array<string, mixed> $scripts */
        $scripts = [];
        if (isset($composerJson['scripts']) && is_array($composerJson['scripts'])) {
            /** @var array<string, mixed> $scripts */
            $scripts = $composerJson['scripts'];
        }

        $tools = $this->detectTools($projectRoot, $composerJson, $scripts);

        $audit = new ComposerAudit(
            projectRoot: $projectRoot,
            composerJson: $composerJson,
            scripts: $scripts,
            tools: $tools,
        );

        self::$cache[$cacheKey] = [
            'composerJsonMtime' => $composerJsonMtime,
            'audit' => $audit,
        ];

        return $audit;
    }

    /**
     * @param array<mixed> $composerJson
     * @param array<string, mixed> $scripts
     * @return array<string, array{tool: string, present: bool, via?: 'require'|'require-dev'|'script'|'bin', run?: string}>
     */
    private function detectTools(string $projectRoot, array $composerJson, array $scripts): array
    {
        $projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);

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
            'mago' => [
                'packages' => ['carthage-software/mago'],
                'bins' => ['mago'],
                'scripts' => [],
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

            if ($present === false) {
                foreach ($hints['bins'] as $bin) {
                    if ($this->hasVendorBinary($projectRoot, $bin)) {
                        $present = true;
                        $via = 'bin';
                        $run = 'vendor/bin/' . $bin;
                        break;
                    }

                    if ($this->findBinaryInPath($bin) !== null) {
                        $present = true;
                        $via = 'bin';
                        $run = $bin;
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

    private function hasVendorBinary(string $projectRoot, string $binary): bool
    {
        $vendorBinDir = $projectRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin';

        $candidates = [
            $vendorBinDir . DIRECTORY_SEPARATOR . $binary,
            $vendorBinDir . DIRECTORY_SEPARATOR . $binary . '.phar',
            $vendorBinDir . DIRECTORY_SEPARATOR . $binary . '.exe',
            $vendorBinDir . DIRECTORY_SEPARATOR . $binary . '.bat',
            $vendorBinDir . DIRECTORY_SEPARATOR . $binary . '.cmd',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                return true;
            }
        }

        return false;
    }

    private function findBinaryInPath(string $binary): ?string
    {
        $path = getenv('PATH');
        if (!is_string($path) || $path === '') {
            return null;
        }

        $extensions = [''];

        $pathExt = getenv('PATHEXT');
        if (is_string($pathExt) && $pathExt !== '') {
            $extensions = array_merge($extensions, array_map('strtolower', array_filter(explode(';', $pathExt))));
        }

        foreach (array_filter(explode(PATH_SEPARATOR, $path)) as $dir) {
            foreach ($extensions as $ext) {
                $candidate = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $binary . $ext;
                if (is_file($candidate) && is_executable($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }
}
