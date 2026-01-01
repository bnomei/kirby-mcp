<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Support;

use Kirby\Cms\App;
use Kirby\Cms\User;

final class UserResolver
{
    public static function resolve(App $kirby, ?string $idOrEmail): ?User
    {
        if (!is_string($idOrEmail)) {
            return null;
        }

        $idOrEmail = trim($idOrEmail);
        if ($idOrEmail === '') {
            return null;
        }

        return $kirby->user($idOrEmail);
    }
}
