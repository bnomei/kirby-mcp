<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Project\ComposerInspector;

function removeComposerInspectorFixtureDir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }

    rmdir($dir);
}

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
    $originalPath = getenv('PATH');
    putenv('PATH=');

    try {
        $audit = (new ComposerInspector())->inspect(cmsPath());

        expect($audit->tools)
            ->toHaveKey('pest')
            ->toHaveKey('phpunit')
            ->toHaveKey('phpstan')
            ->toHaveKey('mago');

        expect($audit->tools['pest']['present'])->toBeFalse();
        expect($audit->tools['phpunit']['present'])->toBeFalse();
        expect($audit->tools['phpstan']['present'])->toBeFalse();
        expect($audit->tools['mago']['present'])->toBeFalse();
    } finally {
        if ($originalPath !== false) {
            putenv('PATH=' . $originalPath);
        } else {
            putenv('PATH');
        }
    }
});

it('detects mago when installed via composer require-dev', function (): void {
    $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-composer-mago-require-' . bin2hex(random_bytes(4));
    mkdir($root, 0777, true);

    $composerJson = [
        'name' => 'test/project',
        'require-dev' => [
            'carthage-software/mago' => '^1.0',
        ],
    ];

    file_put_contents($root . DIRECTORY_SEPARATOR . 'composer.json', json_encode($composerJson, JSON_THROW_ON_ERROR));

    try {
        $audit = (new ComposerInspector())->inspect($root);

        expect($audit->tools)->toHaveKey('mago');
        expect($audit->tools['mago']['present'])->toBeTrue();
        expect($audit->tools['mago']['via'])->toBe('require-dev');
        expect($audit->tools['mago']['run'])->toBe('vendor/bin/mago');
    } finally {
        removeComposerInspectorFixtureDir($root);
    }
});

it('detects mago when vendor/bin/mago exists even without composer dependency', function (): void {
    $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-composer-mago-bin-' . bin2hex(random_bytes(4));
    $vendorBin = $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin';
    mkdir($vendorBin, 0777, true);

    file_put_contents($root . DIRECTORY_SEPARATOR . 'composer.json', json_encode(['name' => 'test/project'], JSON_THROW_ON_ERROR));

    $magoBin = $vendorBin . DIRECTORY_SEPARATOR . 'mago';
    file_put_contents($magoBin, "#!/usr/bin/env sh\necho mago\n");
    chmod($magoBin, 0755);

    try {
        $audit = (new ComposerInspector())->inspect($root);

        expect($audit->tools)->toHaveKey('mago');
        expect($audit->tools['mago']['present'])->toBeTrue();
        expect($audit->tools['mago']['via'])->toBe('bin');
        expect($audit->tools['mago']['run'])->toBe('vendor/bin/mago');
    } finally {
        removeComposerInspectorFixtureDir($root);
    }
});

it('suggests php vendor/bin/mago.phar when only a non-executable phar exists', function (): void {
    $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-composer-mago-phar-' . bin2hex(random_bytes(4));
    $vendorBin = $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin';
    mkdir($vendorBin, 0777, true);

    file_put_contents($root . DIRECTORY_SEPARATOR . 'composer.json', json_encode(['name' => 'test/project'], JSON_THROW_ON_ERROR));

    $magoPhar = $vendorBin . DIRECTORY_SEPARATOR . 'mago.phar';
    file_put_contents($magoPhar, 'mago');
    chmod($magoPhar, 0644);

    try {
        $audit = (new ComposerInspector())->inspect($root);

        expect($audit->tools)->toHaveKey('mago');
        expect($audit->tools['mago']['present'])->toBeTrue();
        expect($audit->tools['mago']['via'])->toBe('bin');
        expect($audit->tools['mago']['run'])->toBe('php vendor/bin/mago.phar');
    } finally {
        removeComposerInspectorFixtureDir($root);
    }
});

it('detects mago when mago is available on PATH', function (): void {
    $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-composer-mago-path-' . bin2hex(random_bytes(4));
    mkdir($root, 0777, true);

    file_put_contents($root . DIRECTORY_SEPARATOR . 'composer.json', json_encode(['name' => 'test/project'], JSON_THROW_ON_ERROR));

    $binDir = $root . DIRECTORY_SEPARATOR . 'bin';
    mkdir($binDir, 0777, true);

    $magoBin = $binDir . DIRECTORY_SEPARATOR . 'mago';
    file_put_contents($magoBin, "#!/usr/bin/env sh\necho mago\n");
    chmod($magoBin, 0755);

    $originalPath = getenv('PATH');
    putenv('PATH=' . $binDir);

    try {
        $audit = (new ComposerInspector())->inspect($root);

        expect($audit->tools)->toHaveKey('mago');
        expect($audit->tools['mago']['present'])->toBeTrue();
        expect($audit->tools['mago']['via'])->toBe('bin');
        expect($audit->tools['mago']['run'])->toBe('mago');
    } finally {
        if ($originalPath !== false) {
            putenv('PATH=' . $originalPath);
        } else {
            putenv('PATH');
        }
        removeComposerInspectorFixtureDir($root);
    }
});
