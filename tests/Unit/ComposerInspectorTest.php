<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Project\ComposerInspector;

it('inspects composer.json for the cms fixture', function (): void {
    $audit = (new ComposerInspector())->inspect(cmsPath());

    expect($audit->composerJson)
        ->toHaveKey('name', 'getkirby/starterkit')
        ->and($audit->composerJson)
        ->toHaveKey('require')
        ->and($audit->composerJson['require'])
        ->toHaveKey('getkirby/cms');

    expect($audit->scripts)->toHaveKey('start');
});

it('detects common tooling as absent in the cms fixture', function (): void {
    $audit = (new ComposerInspector())->inspect(cmsPath());

    expect($audit->tools)
        ->toHaveKey('pest')
        ->toHaveKey('phpunit')
        ->toHaveKey('phpstan');

    expect($audit->tools['pest']['present'])->toBeFalse();
    expect($audit->tools['phpunit']['present'])->toBeFalse();
    expect($audit->tools['phpstan']['present'])->toBeFalse();
});
