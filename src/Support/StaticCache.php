<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Support;

use WeakReference;

final class StaticCache
{
    /** @var array<string, array{value:mixed, expiresAt:int|null, isWeak:bool}> */
    private static array $store = [];

    public static function has(string $key): bool
    {
        if (!array_key_exists($key, self::$store)) {
            return false;
        }

        $entry = self::$store[$key];
        $expiresAt = $entry['expiresAt'];
        if (is_int($expiresAt) && $expiresAt > 0 && time() >= $expiresAt) {
            unset(self::$store[$key]);
            return false;
        }

        // Check if weak reference has been garbage collected
        if ($entry['isWeak'] === true) {
            /** @var WeakReference<object> $ref */
            $ref = $entry['value'];
            if ($ref->get() === null) {
                unset(self::$store[$key]);
                return false;
            }
        }

        return true;
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

        // Use WeakReference for objects to allow garbage collection
        $isWeak = is_object($value);
        $storedValue = $isWeak ? WeakReference::create($value) : $value;

        self::$store[$key] = [
            'value' => $storedValue,
            'expiresAt' => $expiresAt,
            'isWeak' => $isWeak,
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

        // Unwrap weak reference
        if ($entry['isWeak'] === true) {
            /** @var WeakReference<object> $ref */
            $ref = $entry['value'];
            $value = $ref->get();
            if ($value === null) {
                // Object was garbage collected
                unset(self::$store[$key]);
                return null;
            }
            return $value;
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
        if (self::has($key)) {
            return self::get($key);
        }

        $value = $compute();
        self::set($key, $value, $ttlSeconds);

        return $value;
    }
}
