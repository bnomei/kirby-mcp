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

it('parses `kirby <command> --help` usage output into args', function (): void {
    $stdout = <<<TEXT
Displays Kirby license information in table or JSON format.

Usage: kirby [-d, --debug] [-f format, --format format] [-h, --help] [--quiet] [command]

Required Arguments:
\tcommand
\t\tThe name of the command

Optional Arguments:
\t-f format, --format format
\t\tOutput format: table or json.
\t--quiet
\t\tSurpresses any output
\t-d, --debug
\t\tEnables debug mode
\t-h, --help
\t\tPrints a usage statement
TEXT;

    $parsed = KirbyCliHelpParser::parseCommandUsage($stdout);

    expect($parsed['description'])->toBe('Displays Kirby license information in table or JSON format.');
    expect($parsed['usage'])->toContain('kirby');

    expect($parsed['required'])->toHaveCount(1);
    expect($parsed['required'][0]['name'])->toBe('command');
    expect($parsed['required'][0]['kind'])->toBe('argument');

    $names = array_map(static fn (array $arg): string => $arg['name'], $parsed['optional']);
    expect($names)->toContain('--quiet');
    expect($names)->toContain('--debug');
    expect($names)->toContain('--help');

    $format = null;
    foreach ($parsed['optional'] as $arg) {
        if ($arg['name'] === '--format format') {
            $format = $arg;
            break;
        }
    }

    expect($format)->not()->toBeNull();
    expect($format['kind'])->toBe('option');
    expect($format['aliases'])->toContain('--format format');
});
