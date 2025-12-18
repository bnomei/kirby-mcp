<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Blueprint\BlueprintScanner;
use Bnomei\KirbyMcp\Blueprint\BlueprintType;

it('scans Kirby cms fixture blueprints from the filesystem', function (): void {
    $result = (new BlueprintScanner())->scan(cmsPath());

    expect($result->errors)->toBeEmpty();

    expect($result->blueprints)->toHaveKey('site');
    expect($result->blueprints['site']->type)->toBe(BlueprintType::Site);

    expect($result->blueprints)->toHaveKey('pages/home');
    expect($result->blueprints['pages/home']->type)->toBe(BlueprintType::Page);

    expect($result->blueprints)->toHaveKey('fields/cover');
    expect($result->blueprints['fields/cover']->type)->toBe(BlueprintType::Field);

    expect($result->blueprints)->toHaveKey('users/default');
    expect($result->blueprints['users/default']->type)->toBe(BlueprintType::User);
});
