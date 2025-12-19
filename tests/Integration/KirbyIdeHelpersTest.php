<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Tools\IdeTools;

/**
 * Clean up IDE helper files from the .kirby-mcp directory.
 * Preserves config.json and other non-IDE files.
 */
function cleanupIdeHelperFiles(string $outputDir): void
{
    $ideHelperFiles = [
        '_ide_helper_kirby_fields.php',
        'kirby-blueprints.index.json',
    ];

    $phpstormMetaDir = $outputDir . DIRECTORY_SEPARATOR . '.phpstorm.meta.php';

    // Remove IDE helper files
    foreach ($ideHelperFiles as $file) {
        $path = $outputDir . DIRECTORY_SEPARATOR . $file;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    // Remove .phpstorm.meta.php directory
    if (is_dir($phpstormMetaDir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($phpstormMetaDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
                continue;
            }

            @unlink($file->getPathname());
        }

        @rmdir($phpstormMetaDir);
    }
}

it('skips existing files when force=false during IDE helper generation', function (): void {
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new IdeTools();
    $outputDir = cmsPath() . '/.kirby-mcp';

    // Clean up any existing IDE helper files (preserve config.json)
    cleanupIdeHelperFiles($outputDir);

    try {
        // First generation with force=true to create files
        $firstGenerate = $tools->generateIdeHelpers(dryRun: false, force: true, preferRuntime: false);

        expect($firstGenerate['ok'])->toBeTrue();
        expect($firstGenerate['dryRun'])->toBeFalse();
        expect($firstGenerate['files'])->toBeArray();
        expect(count($firstGenerate['files']))->toBeGreaterThan(0);

        // Check that files were created (not skipped)
        $createdFiles = array_filter(
            $firstGenerate['files'],
            static fn (array $file): bool => in_array($file['action'], ['create', 'overwrite'], true)
        );
        expect(count($createdFiles))->toBeGreaterThan(0);

        // Second generation with force=false should skip existing files
        $secondGenerate = $tools->generateIdeHelpers(dryRun: false, force: false, preferRuntime: false);

        expect($secondGenerate['ok'])->toBeTrue();

        // Check that all previously created files are now skipped
        $skippedFiles = array_filter(
            $secondGenerate['files'],
            static fn (array $file): bool => $file['action'] === 'skip'
        );

        expect(count($skippedFiles))->toBeGreaterThan(0);

        // Verify the skip reason mentions force=false
        $firstSkipped = reset($skippedFiles);
        expect($firstSkipped['reason'])->toContain('force=false');
    } finally {
        // Clean up generated IDE helper files
        cleanupIdeHelperFiles($outputDir);
    }
});

it('generates files when force=true overwrites existing files', function (): void {
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $tools = new IdeTools();
    $outputDir = cmsPath() . '/.kirby-mcp';

    // Clean up any existing IDE helper files (preserve config.json)
    cleanupIdeHelperFiles($outputDir);

    try {
        // First generation to create files
        $firstGenerate = $tools->generateIdeHelpers(dryRun: false, force: true, preferRuntime: false);
        expect($firstGenerate['ok'])->toBeTrue();

        // Find files that were created or overwritten (both mean files were written)
        $writtenFiles = array_filter(
            $firstGenerate['files'],
            static fn (array $file): bool => in_array($file['action'], ['create', 'overwrite'], true)
        );
        expect(count($writtenFiles))->toBeGreaterThan(0);

        // Second generation with force=true should overwrite
        $secondGenerate = $tools->generateIdeHelpers(dryRun: false, force: true, preferRuntime: false);

        expect($secondGenerate['ok'])->toBeTrue();

        // All files should be overwritten (not skipped)
        $overwrittenFiles = array_filter(
            $secondGenerate['files'],
            static fn (array $file): bool => $file['action'] === 'overwrite'
        );

        expect(count($overwrittenFiles))->toBeGreaterThan(0);

        // No files should be skipped
        $skippedFiles = array_filter(
            $secondGenerate['files'],
            static fn (array $file): bool => $file['action'] === 'skip'
        );

        expect(count($skippedFiles))->toBe(0);
    } finally {
        // Clean up generated IDE helper files
        cleanupIdeHelperFiles($outputDir);
    }
});
