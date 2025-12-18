<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Runtime;

use Kirby\CLI\CLI;
use Kirby\Cms\Blueprint;
use Kirby\Data\Yaml;
use Kirby\Filesystem\F;
use Throwable;

final class McpBlueprintCommand extends McpRuntimeCommand
{
    /**
     * @return array{
     *   description: string,
     *   args: array<string, mixed>,
     *   command: callable(CLI): void
     * }
     */
    public static function definition(): array
    {
        return [
            'description' => 'Read a Kirby blueprint by id (supports plugin blueprint extensions) and return structured JSON for MCP.',
            'args' => [
                'id' => [
                    'description' => 'Blueprint id (e.g. pages/home, tabs/seo, site).',
                ],
            ],
            'command' => [self::class, 'run'],
        ];
    }

    public static function run(CLI $cli): void
    {
        $kirby = self::kirbyOrEmitError($cli);
        if ($kirby === null) {
            return;
        }

        $id = $cli->arg('id');
        if (!is_string($id) || trim($id) === '') {
            self::emit($cli, [
                'ok' => false,
                'error' => [
                    'class' => 'InvalidArgumentException',
                    'message' => 'Blueprint id is required.',
                    'code' => 0,
                ],
            ]);
            return;
        }

        $id = trim($id);

        try {
            $blueprintsRoot = $kirby->root('blueprints');
            $blueprintsRoot = is_string($blueprintsRoot) ? rtrim($blueprintsRoot, DIRECTORY_SEPARATOR) : null;

            $fileInfo = self::resolveBlueprintFileInfo($kirby, $id, $blueprintsRoot);

            $data = Blueprint::load($id);
            [$displayName, $displayNameSource] = self::deriveDisplayName($id, $data, self::activeYamlFile($fileInfo));

            self::emit($cli, [
                'ok' => true,
                'id' => $id,
                'type' => self::blueprintType($id),
                'displayName' => $displayName,
                'displayNameSource' => $displayNameSource,
                'file' => $fileInfo,
                'data' => $data,
            ]);
        } catch (Throwable $exception) {
            self::emit($cli, [
                'ok' => false,
                'id' => $id,
                'error' => self::errorArray($exception, self::traceForCli($cli, $exception)),
            ]);
        }
    }

    /**
     * @return array{
     *   activeSource: 'file'|'extension'|null,
     *   overriddenByFile: bool,
     *   file: array{absolutePath:string, relativeToBlueprintsRoot:string|null}|null,
     *   extension: array{kind:'file'|'array'|'callable'|'unknown', absolutePath:string|null}|null
     * }
     */
    private static function resolveBlueprintFileInfo(object $kirby, string $id, ?string $blueprintsRoot): array
    {
        $siteFile = null;
        if (is_string($blueprintsRoot) && $blueprintsRoot !== '') {
            $yml = $blueprintsRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $id) . '.yml';
            $yaml = $blueprintsRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $id) . '.yaml';

            if (F::exists($yml, $blueprintsRoot) === true) {
                $siteFile = $yml;
            } elseif (F::exists($yaml, $blueprintsRoot) === true) {
                $siteFile = $yaml;
            }
        }

        $extension = $kirby->extension('blueprints', $id, null);
        $extensionKind = 'unknown';
        $extensionPath = null;

        if (is_string($extension)) {
            $extensionKind = 'file';
            $extensionPath = is_file($extension) ? $extension : null;
        } elseif (is_array($extension)) {
            $extensionKind = 'array';
        } elseif (is_callable($extension)) {
            $extensionKind = 'callable';
        }

        $activeSource = null;
        if (is_string($siteFile)) {
            $activeSource = 'file';
        } elseif ($extension !== null) {
            $activeSource = 'extension';
        }

        $relativeToRoot = null;
        if (is_string($siteFile) && is_string($blueprintsRoot) && $blueprintsRoot !== '') {
            $relativeToRoot = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', substr($siteFile, strlen($blueprintsRoot))), '/');
        }

        return [
            'activeSource' => $activeSource,
            'overriddenByFile' => is_string($siteFile) && $extension !== null,
            'file' => is_string($siteFile) ? [
                'absolutePath' => $siteFile,
                'relativeToBlueprintsRoot' => $relativeToRoot,
            ] : null,
            'extension' => $extension !== null ? [
                'kind' => $extensionKind,
                'absolutePath' => $extensionPath,
            ] : null,
        ];
    }

    /**
     * @param array<mixed> $data
     * @return array{0:string,1:'title'|'name'|'label'|'id'}
     */
    private static function deriveDisplayName(string $id, array $data, ?string $yamlFile = null): array
    {
        if (is_string($yamlFile) && $yamlFile !== '' && (str_ends_with($yamlFile, '.yml') || str_ends_with($yamlFile, '.yaml'))) {
            try {
                $raw = Yaml::read($yamlFile);
                if (is_array($raw)) {
                    return self::deriveDisplayNameFromArray($id, $raw);
                }
            } catch (Throwable) {
                // fall back to resolved blueprint data
            }
        }

        return self::deriveDisplayNameFromArray($id, $data);
    }

    /**
     * @param array<mixed> $data
     * @return array{0:string,1:'title'|'name'|'label'|'id'}
     */
    private static function deriveDisplayNameFromArray(string $id, array $data): array
    {
        $fallback = $id;
        $lastSlash = strrpos($fallback, '/');
        if ($lastSlash !== false) {
            $fallback = substr($fallback, $lastSlash + 1);
        }

        $fallback = trim($fallback);
        if ($fallback === '') {
            $fallback = $id;
        }

        foreach (['title', 'name', 'label'] as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            $string = self::stringFromNameValue($value);
            if ($string !== null) {
                /** @var 'title'|'name'|'label' $key */
                return [$string, $key];
            }
        }

        return [$fallback, 'id'];
    }

    /**
     * @param array<string, mixed> $fileInfo
     */
    private static function activeYamlFile(array $fileInfo): ?string
    {
        $activeSource = $fileInfo['activeSource'] ?? null;
        if ($activeSource === 'file') {
            $file = $fileInfo['file']['absolutePath'] ?? null;
            return is_string($file) && $file !== '' ? $file : null;
        }

        if ($activeSource === 'extension') {
            $path = $fileInfo['extension']['absolutePath'] ?? null;
            return is_string($path) && $path !== '' ? $path : null;
        }

        return null;
    }

    private static function stringFromNameValue(mixed $value): ?string
    {
        if (is_string($value)) {
            $value = trim($value);
            return $value !== '' ? $value : null;
        }

        if (!is_array($value)) {
            return null;
        }

        $en = $value['en'] ?? null;
        if (is_string($en) && trim($en) !== '') {
            return trim($en);
        }

        foreach ($value as $v) {
            if (!is_string($v)) {
                continue;
            }

            $v = trim($v);
            if ($v !== '') {
                return $v;
            }
        }

        return null;
    }

    private static function blueprintType(string $id): string
    {
        if (!str_contains($id, '/')) {
            return $id;
        }

        $first = explode('/', $id, 2)[0];
        return $first !== '' ? $first : 'unknown';
    }

}
