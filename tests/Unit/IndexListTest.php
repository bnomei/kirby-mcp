<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Support\IndexList;

it('paginates ids and clamps cursor/limit', function (): void {
    $ids = ['a', 'b', 'c', 'd'];

    $result = IndexList::paginateIds($ids, -2, -5);

    expect($result['ids'])->toBe($ids);
    expect($result['pagination'])->toBe([
        'cursor' => 0,
        'limit' => 0,
        'nextCursor' => null,
        'hasMore' => false,
        'returned' => 4,
        'total' => 4,
    ]);

    $result = IndexList::paginateIds($ids, 1, 2);

    expect($result['ids'])->toBe(['b', 'c']);
    expect($result['pagination']['nextCursor'])->toBe(3);
    expect($result['pagination']['hasMore'])->toBeTrue();
    expect($result['pagination']['returned'])->toBe(2);
    expect($result['pagination']['total'])->toBe(4);
});

it('selects fields and ensures id', function (): void {
    $entry = [
        'name' => 'Alpha',
        'count' => 3,
    ];

    $selected = IndexList::selectFields($entry, ['name', '', 'name'], 'abc');

    expect($selected)->toHaveKey('name', 'Alpha');
    expect($selected)->toHaveKey('id', 'abc');
    expect($selected)->not()->toHaveKey('count');

    $selected = IndexList::selectFields($entry, [], 'abc');
    expect($selected)->toBe($entry);

    $selected = IndexList::selectFields($entry, ['id'], 'override');
    expect($selected)->toBe(['id' => 'override']);
});
