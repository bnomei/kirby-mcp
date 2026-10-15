<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Project;

final class RuntimeCompatibility
{
    public static function supports(?string $version): bool
    {
        return is_string($version)
            && preg_match('/^6\.\d+\.\d+(?:-[a-zA-Z0-9.-]+)?$/D', $version) === 1
            && version_compare($version, '6.0.0-beta.1', '>=');
    }

    public static function requireSupported(?string $version): void
    {
        if (!self::supports($version)) {
            throw new \RuntimeException('Kirby MCP 6 requires Kirby 6.0.0-beta.1 or newer in major 6; detected '
                . ($version ?? 'unknown') . '. Use Kirby MCP 1.x for Kirby 5, or --global for projectless reference access.');
        }
    }
}
