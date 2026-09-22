<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Cli\PhpBinaryResolver;

it('resolves an explicitly configured PHP CLI binary and trims matching quotes', function (): void {
    $resolver = new PhpBinaryResolver('', sys_get_temp_dir() . '/missing-php-bindir');

    expect($resolver->resolve('  "/opt/php/bin/php"  '))->toBe('/opt/php/bin/php');
    expect($resolver->resolve("  '/another/php'  "))->toBe('/another/php');
});

it('falls back to PHP_BINARY and then an executable PHP_BINDIR candidate', function (): void {
    $resolver = new PhpBinaryResolver('/configured/php', sys_get_temp_dir() . '/missing-php-bindir');
    expect($resolver->resolve(false))->toBe('/configured/php');

    $directory = sys_get_temp_dir() . '/kirby-mcp-php-bindir-' . bin2hex(random_bytes(4));
    mkdir($directory, 0700);
    $candidate = $directory . DIRECTORY_SEPARATOR . (PHP_OS_FAMILY === 'Windows' ? 'php.exe' : 'php');
    file_put_contents($candidate, 'php');
    chmod($candidate, 0700);

    try {
        $fallback = new PhpBinaryResolver('', $directory);
        expect($fallback->resolve('  '))->toBe($candidate);
    } finally {
        unlink($candidate);
        rmdir($directory);
    }
});

it('fails before process creation when no PHP CLI binary can be resolved', function (): void {
    $resolver = new PhpBinaryResolver('', sys_get_temp_dir() . '/missing-php-bindir-' . bin2hex(random_bytes(4)));

    expect(fn (): string => $resolver->resolve(false))
        ->toThrow(RuntimeException::class, KirbyCliRunner::ENV_PHP_BINARY);
});
