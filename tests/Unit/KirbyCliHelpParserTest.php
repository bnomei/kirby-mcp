<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliHelpParser;

it('parses `kirby help` output into commands and sections', function (): void {
    $stdout = <<<TEXT
Kirby CLI 1.9.0

Core commands:
- kirby version
- kirby roots
- kirby make:template

Have fun with the Kirby CLI!
TEXT;

    $parsed = KirbyCliHelpParser::parse($stdout);

    expect($parsed['cliVersion'])->toBe('1.9.0');
    expect($parsed['commands'])->toContain('version');
    expect($parsed['commands'])->toContain('roots');
    expect($parsed['commands'])->toContain('make:template');

    expect($parsed['sections'])->toHaveKey('core');
    expect($parsed['sections']['core'])->toContain('version');
});
