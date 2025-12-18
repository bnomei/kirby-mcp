<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Runtime;

use Kirby\Cms\App;
use Kirby\Cms\Page;

final class McpPageResolver
{
    public static function resolve(App $kirby, ?string $idOrUuid): ?Page
    {
        if (!is_string($idOrUuid)) {
            return null;
        }

        $idOrUuid = trim($idOrUuid);
        if ($idOrUuid === '') {
            return null;
        }

        $page = $kirby->page($idOrUuid);
        if ($page !== null) {
            return $page;
        }

        if (!str_contains($idOrUuid, '://')) {
            return $kirby->page('page://' . $idOrUuid);
        }

        return null;
    }
}
