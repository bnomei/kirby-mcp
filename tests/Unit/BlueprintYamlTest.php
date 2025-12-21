<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Blueprint\BlueprintYaml;

it('parses all starterkit blueprint YAML files', function (): void {
    $yaml = new BlueprintYaml();

    $root = __DIR__ . '/../cms/site/blueprints';
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    );

    /** @var SplFileInfo $file */
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $filename = $file->getFilename();
        if (!str_ends_with($filename, '.yml') && !str_ends_with($filename, '.yaml')) {
            continue;
        }

        $data = $yaml->parseFile($file->getPathname());
        expect($data)->toBeArray();
    }
});

it('throws when blueprint YAML cannot be parsed', function (): void {
    $yaml = new BlueprintYaml();
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-bp-yaml-invalid-' . bin2hex(random_bytes(4));
    mkdir($dir, 0777, true);
    $path = $dir . DIRECTORY_SEPARATOR . 'invalid.yml';
    file_put_contents($path, 'fields: [');

    try {
        expect(fn () => $yaml->parseFile($path))
            ->toThrow(RuntimeException::class, 'Failed to parse blueprint YAML: ' . $path);
    } finally {
        @unlink($path);
        @rmdir($dir);
    }
});

it('throws when blueprint YAML does not parse to an array', function (): void {
    $yaml = new BlueprintYaml();
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-bp-yaml-scalar-' . bin2hex(random_bytes(4));
    mkdir($dir, 0777, true);
    $path = $dir . DIRECTORY_SEPARATOR . 'scalar.yml';
    file_put_contents($path, 'just a string');

    try {
        expect(fn () => $yaml->parseFile($path))
            ->toThrow(RuntimeException::class, 'Blueprint YAML did not parse to an array: ' . $path);
    } finally {
        @unlink($path);
        @rmdir($dir);
    }
});
