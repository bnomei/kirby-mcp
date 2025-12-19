<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Support;

final class StaticCache
{
    /** @var array<string, array{value:mixed, expiresAt:int|null}> */
    private static array $store = [];

    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }

    public static function forget(string $key): void
    {
        unset(self::$store[$key]);
    }

    public static function clear(): void
    {
        self::$store = [];
    }

    public static function clearPrefix(string $prefix): int
    {
        if ($prefix === '') {
            $count = count(self::$store);
            self::$store = [];
            return $count;
        }

        $removed = 0;
        foreach (array_keys(self::$store) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset(self::$store[$key]);
                $removed++;
            }
        }

        return $removed;
    }

    public static function set(string $key, mixed $value, ?int $ttlSeconds = null): void
    {
        $expiresAt = null;
        if (is_int($ttlSeconds) && $ttlSeconds > 0) {
            $expiresAt = time() + $ttlSeconds;
        }

        self::$store[$key] = [
            'value' => $value,
            'expiresAt' => $expiresAt,
        ];
    }

    public static function get(string $key): mixed
    {
        if (!array_key_exists($key, self::$store)) {
            return null;
        }

        $entry = self::$store[$key];
        $expiresAt = $entry['expiresAt'];
        if (is_int($expiresAt) && $expiresAt > 0 && time() >= $expiresAt) {
            unset(self::$store[$key]);
            return null;
        }

        return $entry['value'];
    }

    /**
     * @template TReturn
     * @param callable(): TReturn $compute
     * @return TReturn
     */
    public static function remember(string $key, callable $compute, ?int $ttlSeconds = null): mixed
    {
        $cached = self::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $value = $compute();
        self::set($key, $value, $ttlSeconds);

        return $value;
    }
}
