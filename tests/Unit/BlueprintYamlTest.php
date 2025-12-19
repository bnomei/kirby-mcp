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
