<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Blueprint\BlueprintFile;
use Bnomei\KirbyMcp\Blueprint\BlueprintScanResult;
use Bnomei\KirbyMcp\Blueprint\BlueprintType;

it('returns blueprint scan data as arrays', function (): void {
    $blueprintA = new BlueprintFile(
        id: 'pages/home',
        type: BlueprintType::Page,
        absolutePath: '/abs/home.yml',
        relativePath: 'site/blueprints/pages/home.yml',
        data: ['title' => 'Home'],
    );

    $blueprintB = new BlueprintFile(
        id: 'pages/blog',
        type: BlueprintType::Page,
        absolutePath: '/abs/blog.yml',
        relativePath: 'site/blueprints/pages/blog.yml',
        data: null,
    );

    $result = new BlueprintScanResult(
        projectRoot: '/project',
        blueprintsRoot: '/project/site/blueprints',
        blueprints: [
            'pages/home' => $blueprintA,
            'pages/blog' => $blueprintB,
        ],
        errors: [
            ['path' => 'bad.yml', 'error' => 'invalid yaml'],
        ],
    );

    expect($result->toArray())->toBe([
        'projectRoot' => '/project',
        'blueprintsRoot' => '/project/site/blueprints',
        'blueprints' => [
            'pages/home' => [
                'id' => 'pages/home',
                'type' => 'page',
                'absolutePath' => '/abs/home.yml',
                'relativePath' => 'site/blueprints/pages/home.yml',
                'displayName' => 'Home',
                'displayNameSource' => 'title',
                'data' => ['title' => 'Home'],
            ],
            'pages/blog' => [
                'id' => 'pages/blog',
                'type' => 'page',
                'absolutePath' => '/abs/blog.yml',
                'relativePath' => 'site/blueprints/pages/blog.yml',
                'displayName' => 'blog',
                'displayNameSource' => 'id',
                'data' => null,
            ],
        ],
        'errors' => [
            ['path' => 'bad.yml', 'error' => 'invalid yaml'],
        ],
    ]);
});
