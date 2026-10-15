<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Resources\CliResources;
use Mcp\Exception\ResourceReadException;

it('lists Kirby CLI commands via kirby://commands', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $data = (new CliResources())->commands();

    expect($data['cli']['exitCode'])->toBe(0);
    expect($data['commands'])->toContain('version');
    expect($data['commands'])->toContain('roots');
    expect($data['sections'])->toHaveKey('core');
});

it('reads help for a specific CLI command via kirby://cli/command/{command}', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $data = (new CliResources())->command('version');

    expect($data['ok'])->toBeTrue();
    expect($data['command'])->toBe('version');
    expect($data['usage'])->toBeString()->not()->toBe('');
    expect($data['args'])->toHaveKey('required');
    expect($data['args'])->toHaveKey('optional');
    expect($data['cli']['exitCode'])->toBe(0);
});

it('rejects invalid CLI command identifiers', function (): void {
    $resource = new CliResources();

    expect(fn () => $resource->command('   '))
        ->toThrow(ResourceReadException::class, 'Command must not be empty.');
    expect(fn () => $resource->command('bad command'))
        ->toThrow(ResourceReadException::class, 'Command must not contain whitespace.');
});

it('blocks generic runtime commands on Kirby 5 while allowing version diagnostics', function (): void {
    $binary = tempnam(sys_get_temp_dir(), 'kirby-mcp-version-');
    if ($binary === false) {
        throw new RuntimeException('Unable to create test CLI.');
    }
    $marker = $binary . '.executed';
    $previous = getenv(KirbyCliRunner::ENV_KIRBY_BIN);
    file_put_contents($binary, '<?php if (($argv[1] ?? "") === "version") { echo "Kirby 5.6.1"; } else { file_put_contents(__FILE__ . ".executed", "yes"); }');
    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());
    try {
        $tools = new \Bnomei\KirbyMcp\Mcp\Tools\CliTools();
        expect(fn () => $tools->runCliCommand('roots'))
            ->toThrow(\Mcp\Exception\ToolCallException::class, 'detected 5.6.1');
        expect(is_file($marker))->toBeFalse();
        $diagnostic = $tools->runCliCommand('version');
        expect($diagnostic['ok'])->toBeTrue();
        expect($diagnostic['stdout'])->toContain('5.6.1');
    } finally {
        unlink($binary);
        if (is_file($marker)) {
            unlink($marker);
        }
        putenv(KirbyCliRunner::ENV_KIRBY_BIN . ($previous === false ? '' : '=' . $previous));
    }
});
