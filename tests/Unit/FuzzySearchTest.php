<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Support\FuzzySearch;

it('matches exact/substring/levenshtein using fuzzySearch', function (): void {
    expect(FuzzySearch::fuzzySearch('config', 'Kirby config lives in site/config/config.php'))->toBeTrue();
    expect(FuzzySearch::fuzzySearch('confi', 'config'))->toBeTrue();
    expect(FuzzySearch::fuzzySearch('confg', 'config'))->toBeTrue();
    expect(FuzzySearch::fuzzySearch('notfound', 'config'))->toBeFalse();
});

it('does not match an unrelated needle against words over 255 bytes', function (): void {
    $longToken = str_repeat('a', 300);

    expect(FuzzySearch::fuzzyLevenshtein('zzz', $longToken))->toBeFalse();
    expect(FuzzySearch::fuzzySearch('zzz', 'intro ' . $longToken . ' outro'))->toBeFalse();
});
