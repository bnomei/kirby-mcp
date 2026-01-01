<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Support;

use Kirby\Cms\App;
use Kirby\Cms\File;

final class FileResolver
{
    public static function resolve(App $kirby, ?string $idOrUuid): ?File
    {
        if (!is_string($idOrUuid)) {
            return null;
        }

        $idOrUuid = trim($idOrUuid);
        if ($idOrUuid === '') {
            return null;
        }

        $file = $kirby->file($idOrUuid);
        if ($file !== null) {
            return $file;
        }

        if (!str_contains($idOrUuid, '://')) {
            return $kirby->file('file://' . $idOrUuid);
        }

        return null;
    }
}
