<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Cli;

final class KirbyCliCommand
{
    public static function isCanonical(string $command): bool
    {
        return preg_match('/\A[A-Za-z0-9_][A-Za-z0-9_-]*(?::[A-Za-z0-9_][A-Za-z0-9_-]*)*\z/', $command) === 1;
    }

    public static function validate(string $command): void
    {
        if (!self::isCanonical($command)) {
            throw new \InvalidArgumentException('Command must use a canonical CLI name: non-empty alphanumeric/underscore/hyphen segments separated by single colons, starting with an alphanumeric or underscore.');
        }
    }
}
