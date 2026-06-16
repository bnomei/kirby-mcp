<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

final class ServerProfile
{
    public const PROJECT = 'project';
    public const GLOBAL_REFERENCE = 'global-reference';

    /**
     * @return array<int, string>
     */
    public static function tools(string $profile): array
    {
        if (self::normalize($profile) !== self::GLOBAL_REFERENCE) {
            return [];
        }

        return [
            'kirby_init',
            'kirby_online',
            'kirby_online_plugins',
            'kirby_search',
            'kirby_tool_suggest',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function resources(string $profile): array
    {
        if (self::normalize($profile) !== self::GLOBAL_REFERENCE) {
            return [];
        }

        return [
            'kirby://blueprints/update-schema',
            'kirby://extensions',
            'kirby://fields',
            'kirby://fields/update-schema',
            'kirby://glossary',
            'kirby://hooks',
            'kirby://kb',
            'kirby://sections',
            'kirby://tools',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function resourceTemplates(string $profile): array
    {
        if (self::normalize($profile) !== self::GLOBAL_REFERENCE) {
            return [];
        }

        return [
            'kirby://blueprint/{type}/update-schema',
            'kirby://extension/{name}',
            'kirby://field/{type}',
            'kirby://field/{type}/update-schema',
            'kirby://glossary/{term}',
            'kirby://hook/{name}',
            'kirby://kb/{path}',
            'kirby://section/{type}',
        ];
    }

    public static function normalize(string $profile): string
    {
        return match ($profile) {
            self::GLOBAL_REFERENCE => self::GLOBAL_REFERENCE,
            default => self::PROJECT,
        };
    }

    public static function isGlobalReference(string $profile): bool
    {
        return self::normalize($profile) === self::GLOBAL_REFERENCE;
    }

    public static function allowsIndexItem(string $profile, string $kind, string $name): bool
    {
        if (!self::isGlobalReference($profile)) {
            return true;
        }

        if ($kind === 'tool') {
            return in_array($name, self::tools($profile), true);
        }

        if ($kind === 'resource') {
            return in_array($name, self::resources($profile), true);
        }

        if ($kind === 'resource_template') {
            if (in_array($name, self::resourceTemplates($profile), true)) {
                return true;
            }

            return self::matchesReferenceTemplateInstance($name);
        }

        return false;
    }

    private static function matchesReferenceTemplateInstance(string $name): bool
    {
        return preg_match('#^kirby://field/[^/]+(?:/update-schema)?$#', $name) === 1
            || preg_match('#^kirby://section/[^/]+$#', $name) === 1;
    }
}
