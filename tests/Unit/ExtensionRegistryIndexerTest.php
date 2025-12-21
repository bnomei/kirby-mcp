<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Support\ExtensionRegistryIndexer;

it('scans php files and maps ids from relative paths', function (): void {
    $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-test-' . bin2hex(random_bytes(8));
    $nested = $root . DIRECTORY_SEPARATOR . 'nested';

    mkdir($nested, 0777, true);

    $fileA = $root . DIRECTORY_SEPARATOR . 'alpha.php';
    $fileB = $nested . DIRECTORY_SEPARATOR . 'beta.php';
    $fileC = $nested . DIRECTORY_SEPARATOR . 'skip.txt';

    file_put_contents($fileA, '<?php');
    file_put_contents($fileB, '<?php');
    file_put_contents($fileC, 'nope');

    try {
        $files = ExtensionRegistryIndexer::scanPhpFiles(
            $root,
            static fn (string $relative): string => str_replace('.php', '', $relative),
        );

        expect($files)->toHaveKey('alpha');
        expect($files)->toHaveKey('nested/beta');
        expect($files['nested/beta']['relativeToRoot'])->toBe('nested/beta.php');
    } finally {
        if (is_file($fileA)) {
            @unlink($fileA);
        }
        if (is_file($fileB)) {
            @unlink($fileB);
        }
        if (is_file($fileC)) {
            @unlink($fileC);
        }
        if (is_dir($nested)) {
            @rmdir($nested);
        }
        if (is_dir($root)) {
            @rmdir($root);
        }
    }
});

it('merges extension and file registries with override metadata', function (): void {
    $extensions = [
        'alpha' => '/tmp/alpha.php',
        'beta' => ['handler' => 'beta'],
        'gamma' => static fn () => null,
    ];

    $files = [
        'alpha' => [
            'absolutePath' => '/project/alpha.php',
            'relativeToRoot' => 'alpha.php',
        ],
        'delta' => [
            'absolutePath' => '/project/delta.php',
            'relativeToRoot' => 'delta.php',
        ],
    ];

    $merged = ExtensionRegistryIndexer::merge(
        $extensions,
        $files,
        [ExtensionRegistryIndexer::class, 'extensionInfoBasic'],
    );

    expect($merged['counts'])->toBe([
        'extensions' => 3,
        'files' => 2,
        'total' => 4,
        'overriddenByFile' => 1,
    ]);

    $byId = [];
    foreach ($merged['items'] as $item) {
        $byId[$item['id']] = $item;
    }

    expect($byId['alpha']['overriddenByFile'])->toBeTrue();
    expect($byId['alpha']['activeSource'])->toBe('file');
    expect($byId['alpha']['sources'])->toBe(['extension', 'file']);
    expect($byId['alpha']['activeAbsolutePath'])->toBe('/project/alpha.php');

    expect($byId['beta']['activeSource'])->toBe('extension');
    expect($byId['beta']['extension']['kind'])->toBe('array');

    expect($byId['gamma']['extension']['kind'])->toBe('callable');
    expect($byId['delta']['activeSource'])->toBe('file');
});

it('filters and paginates registry items', function (): void {
    $items = [
        [
            'id' => 'alpha',
            'activeSource' => 'file',
            'sources' => ['extension', 'file'],
            'overriddenByFile' => true,
            'activeAbsolutePath' => '/project/alpha.php',
            'file' => ['absolutePath' => '/project/alpha.php', 'relativeToRoot' => 'alpha.php'],
            'extension' => ['kind' => 'file', 'absolutePath' => '/tmp/alpha.php'],
        ],
        [
            'id' => 'beta',
            'activeSource' => 'extension',
            'sources' => ['extension'],
            'overriddenByFile' => false,
            'activeAbsolutePath' => '/tmp/beta.php',
            'file' => null,
            'extension' => ['kind' => 'file', 'absolutePath' => '/tmp/beta.php'],
        ],
    ];

    $filtered = ExtensionRegistryIndexer::filterAndPaginateItems($items, 'file', true);

    expect($filtered['items'])->toHaveCount(1);
    expect($filtered['items'][0]['id'])->toBe('alpha');
    expect($filtered['filters'])->toBe([
        'activeSource' => 'file',
        'overriddenOnly' => true,
    ]);

    $paged = ExtensionRegistryIndexer::filterAndPaginateItems($items, null, false, 0, 1);
    expect($paged['pagination']['returned'])->toBe(1);
    expect($paged['pagination']['hasMore'])->toBeTrue();
    expect($paged['pagination']['nextCursor'])->toBe(1);
});

it('splits representation suffixes and detects extension kinds', function (): void {
    expect(ExtensionRegistryIndexer::splitRepresentation('notes.json'))->toBe(['notes', 'json']);
    expect(ExtensionRegistryIndexer::splitRepresentation('notes.jsonld'))->toBe(['notes.jsonld', null]);
    expect(ExtensionRegistryIndexer::splitRepresentation('a.b.xml'))->toBe(['a.b', 'xml']);

    expect(ExtensionRegistryIndexer::extensionInfoBasic('/tmp/file.php'))->toBe([
        'kind' => 'file',
        'absolutePath' => '/tmp/file.php',
    ]);

    expect(ExtensionRegistryIndexer::extensionInfoBasic(['handler' => true]))->toBe([
        'kind' => 'array',
        'absolutePath' => null,
    ]);

    expect(ExtensionRegistryIndexer::extensionInfoBasic(static fn () => null))->toBe([
        'kind' => 'callable',
        'absolutePath' => null,
    ]);

    expect(ExtensionRegistryIndexer::extensionInfoBasic(123))->toBe([
        'kind' => 'unknown',
        'absolutePath' => null,
    ]);
});
