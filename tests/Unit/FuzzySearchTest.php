<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Support\FuzzySearch;

it('matches exact/substring/levenshtein using fuzzySearch', function (): void {
    expect(FuzzySearch::fuzzySearch('config', 'Kirby config lives in site/config/config.php'))->toBeTrue();
    expect(FuzzySearch::fuzzySearch('confi', 'config'))->toBeTrue(); // substring
    expect(FuzzySearch::fuzzySearch('confg', 'config'))->toBeTrue(); // fuzzy levenshtein
    expect(FuzzySearch::fuzzySearch('notfound', 'config'))->toBeFalse();
});
