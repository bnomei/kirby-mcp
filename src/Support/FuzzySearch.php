<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Support;

final class FuzzySearch
{
    public static function fuzzySearch(string $needle, ?string $haystack = null, int $maxDist = 2): bool
    {
        $needle = trim($needle);
        if ($needle === '') {
            return false;
        }

        $needle = preg_replace('/[^\\p{L}\\p{N}]/u', ' ', $needle) ?? $needle;
        $needle = preg_replace('/\\s+/u', ' ', $needle) ?? $needle;
        $needle = trim($needle);
        if ($needle === '') {
            return false;
        }

        $needleLower = mb_strtolower($needle);

        $haystack = is_string($haystack) ? trim($haystack) : null;
        if (empty($haystack)) {
            return false;
        }

        $haystack = preg_replace('/[^\\p{L}\\p{N}]/u', ' ', $haystack) ?? $haystack;
        $haystack = preg_replace('/\\s+/u', ' ', $haystack) ?? $haystack;
        $haystack = explode(' ', trim($haystack));

        foreach ($haystack as $word) {
            $wordLower = mb_strtolower($word);

            if (
                $needleLower === $wordLower
                || str_contains($wordLower, $needleLower)
                || self::fuzzyLevenshtein($needleLower, $wordLower, $maxDist)
            ) {
                return true;
            }
        }

        return false;
    }

    public static function fuzzyLevenshtein(string $needle, string $haystack, int $maxDist = 2): bool
    {
        $dist = levenshtein(mb_strtolower($needle), mb_strtolower($haystack));

        return $dist <= $maxDist;
    }
}
