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
