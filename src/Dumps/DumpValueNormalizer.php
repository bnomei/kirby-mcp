<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Dumps;

final class DumpValueNormalizer
{
    private const DEFAULT_MAX_DEPTH = 4;
    private const DEFAULT_MAX_ITEMS = 50;
    private const DEFAULT_MAX_STRING_CHARS = 2000;

    public static function normalize(
        mixed $value,
        int $maxDepth = self::DEFAULT_MAX_DEPTH,
        int $maxItems = self::DEFAULT_MAX_ITEMS,
        int $maxStringChars = self::DEFAULT_MAX_STRING_CHARS,
    ): mixed {
        $maxDepth = max(0, $maxDepth);
        $maxItems = max(1, $maxItems);
        $maxStringChars = max(0, $maxStringChars);

        return self::normalizeValue($value, $maxDepth, $maxItems, $maxStringChars);
    }

    private static function normalizeValue(
        mixed $value,
        int $depth,
        int $maxItems,
        int $maxStringChars,
    ): mixed {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value)) {
            if ($maxStringChars > 0 && mb_strlen($value) > $maxStringChars) {
                return mb_substr($value, 0, $maxStringChars) . '…';
            }
            return $value;
        }

        if ($depth <= 0) {
            return self::summary($value);
        }

        if (is_array($value)) {
            $out = [];
            $count = 0;
            foreach ($value as $key => $item) {
                if ($count >= $maxItems) {
                    $out['__truncated__'] = true;
                    $out['__total__'] = count($value);
                    break;
                }

                $normalizedKey = is_string($key) ? $key : (string) $key;
                $out[$normalizedKey] = self::normalizeValue($item, $depth - 1, $maxItems, $maxStringChars);
                $count++;
            }
            return $out;
        }

        if (is_object($value)) {
            $class = $value::class;

            if ($value instanceof \JsonSerializable) {
                try {
                    return [
                        '__class__' => $class,
                        '__json__' => self::normalizeValue($value->jsonSerialize(), $depth - 1, $maxItems, $maxStringChars),
                    ];
                } catch (\Throwable) {
                    // ignore
                }
            }

            if (method_exists($value, '__toString')) {
                try {
                    $string = (string) $value;
                    return [
                        '__class__' => $class,
                        '__toString__' => self::normalizeValue($string, $depth - 1, $maxItems, $maxStringChars),
                    ];
                } catch (\Throwable) {
                    // ignore
                }
            }

            return ['__class__' => $class];
        }

        if (is_resource($value)) {
            return ['__resource__' => get_resource_type($value)];
        }

        return self::summary($value);
    }

    private static function summary(mixed $value): array
    {
        return [
            '__type__' => gettype($value),
        ];
    }
}
