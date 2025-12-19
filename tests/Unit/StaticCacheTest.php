<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Support\StaticCache;

it('stores and retrieves values in StaticCache', function (): void {
    StaticCache::clear();

    StaticCache::set('k', ['v' => 1]);

    expect(StaticCache::has('k'))->toBeTrue();
    expect(StaticCache::get('k'))->toBe(['v' => 1]);
});

it('expires values in StaticCache when ttl elapses', function (): void {
    StaticCache::clear();

    StaticCache::set('k', 'v', 1);

    usleep(1_100_000);

    expect(StaticCache::get('k'))->toBeNull();
});

it('clears values by prefix in StaticCache', function (): void {
    StaticCache::clear();

    StaticCache::set('a:1', 1);
    StaticCache::set('a:2', 2);
    StaticCache::set('b:1', 3);

    expect(StaticCache::clearPrefix('a:'))->toBe(2);

    expect(StaticCache::get('a:1'))->toBeNull();
    expect(StaticCache::get('a:2'))->toBeNull();
    expect(StaticCache::get('b:1'))->toBe(3);
});

it('stores objects using weak references in StaticCache', function (): void {
    StaticCache::clear();

    $obj = new stdClass();
    $obj->value = 'test';

    StaticCache::set('obj', $obj);

    // Object should be retrievable while reference exists
    expect(StaticCache::has('obj'))->toBeTrue();
    $retrieved = StaticCache::get('obj');
    expect($retrieved)->toBeInstanceOf(stdClass::class);
    expect($retrieved->value)->toBe('test');
});

it('returns null for garbage collected weak referenced objects', function (): void {
    StaticCache::clear();

    // Create object in a closure so it goes out of scope
    $setObject = function (): void {
        $obj = new stdClass();
        $obj->value = 'temporary';
        StaticCache::set('temp_obj', $obj);
    };

    $setObject();

    // Force garbage collection
    gc_collect_cycles();

    // The weak reference should now return null
    expect(StaticCache::get('temp_obj'))->toBeNull();
    expect(StaticCache::has('temp_obj'))->toBeFalse();
});

it('caches null values correctly with weak references', function (): void {
    StaticCache::clear();

    StaticCache::set('null_key', null);

    expect(StaticCache::has('null_key'))->toBeTrue();
    expect(StaticCache::get('null_key'))->toBeNull();
});

it('remembers objects via weak references', function (): void {
    StaticCache::clear();

    $callCount = 0;
    $factory = function () use (&$callCount): stdClass {
        $callCount++;
        $obj = new stdClass();
        $obj->id = $callCount;
        return $obj;
    };

    // First call should compute
    $result1 = StaticCache::remember('remember_obj', $factory);
    expect($result1->id)->toBe(1);
    expect($callCount)->toBe(1);

    // Second call should return cached (while $result1 holds reference)
    $result2 = StaticCache::remember('remember_obj', $factory);
    expect($result2->id)->toBe(1);
    expect($callCount)->toBe(1);
});
