<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Dumps\DumpValueNormalizer;

it('normalizes scalars and truncates long strings', function (): void {
    expect(DumpValueNormalizer::normalize(null))->toBeNull();
    expect(DumpValueNormalizer::normalize(true))->toBeTrue();
    expect(DumpValueNormalizer::normalize(42))->toBe(42);
    expect(DumpValueNormalizer::normalize(3.14))->toBe(3.14);

    $value = 'abcdefghij';
    $normalized = DumpValueNormalizer::normalize($value, maxStringChars: 4);
    expect($normalized)->toBe('abcd' . "\u{2026}");
});

it('truncates arrays beyond max items and summarizes at max depth', function (): void {
    $normalized = DumpValueNormalizer::normalize([1, 2, 3], maxItems: 2);

    expect($normalized['0'])->toBe(1);
    expect($normalized['1'])->toBe(2);
    expect($normalized['__truncated__'])->toBeTrue();
    expect($normalized['__total__'])->toBe(3);

    $summary = DumpValueNormalizer::normalize(['nested' => ['value' => 1]], maxDepth: 0);
    expect($summary)->toBe(['__type__' => 'array']);
});

it('normalizes objects and resources', function (): void {
    $jsonObject = new class () implements JsonSerializable {
        public function jsonSerialize(): mixed
        {
            return ['name' => 'kirby'];
        }
    };

    $stringObject = new class () {
        public function __toString(): string
        {
            return 'hello';
        }
    };

    $plainObject = new class () {};

    expect(DumpValueNormalizer::normalize($jsonObject))->toBe([
        '__class__' => $jsonObject::class,
        '__json__' => ['name' => 'kirby'],
    ]);

    expect(DumpValueNormalizer::normalize($stringObject))->toBe([
        '__class__' => $stringObject::class,
        '__toString__' => 'hello',
    ]);

    expect(DumpValueNormalizer::normalize($plainObject))->toBe([
        '__class__' => $plainObject::class,
    ]);

    $handle = fopen('php://memory', 'r');
    expect($handle)->not->toBeFalse();
    assert(is_resource($handle));

    try {
        expect(DumpValueNormalizer::normalize($handle))->toBe([
            '__resource__' => 'stream',
        ]);
    } finally {
        fclose($handle);
    }
});
