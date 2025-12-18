<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Completion\BlueprintIdCompletionProvider;
use Bnomei\KirbyMcp\Mcp\Completion\KirbyHostCompletionProvider;

it('suggests URL-encoded blueprint ids for the blueprint resource template', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $provider = new BlueprintIdCompletionProvider();
    $completions = $provider->getCompletions('pages');

    expect($completions)->toContain('pages%2Fhome');

    foreach ($completions as $value) {
        expect($value)->toBeString();
        expect($value)->not()->toContain('/');
    }
});

it('suggests host names derived from config.{host}.php files', function (): void {
    $binary = realpath(__DIR__ . '/../../vendor/bin/kirby');
    expect($binary)->not()->toBeFalse();

    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $binary);
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $path = cmsPath() . '/site/config/config.localhost.php';
    $created = false;

    try {
        if (!is_file($path)) {
            file_put_contents($path, "<?php\n\nreturn [];\n");
            $created = true;
        }

        $provider = new KirbyHostCompletionProvider();
        $completions = $provider->getCompletions('');

        expect($completions)->toContain('localhost');
        expect($provider->getCompletions('loc'))->toBe(['localhost']);
    } finally {
        if ($created && is_file($path)) {
            @unlink($path);
        }
    }
});
