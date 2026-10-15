<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Completion\BlueprintIdCompletionProvider;
use Bnomei\KirbyMcp\Mcp\Completion\ExtensionNameCompletionProvider;
use Bnomei\KirbyMcp\Mcp\Completion\HookNameCompletionProvider;
use Bnomei\KirbyMcp\Mcp\Completion\KirbyHostCompletionProvider;
use Bnomei\KirbyMcp\Mcp\Completion\PanelFieldTypeCompletionProvider;
use Bnomei\KirbyMcp\Mcp\Completion\PanelSectionTypeCompletionProvider;

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

it('suggests Kirby Panel field types', function (): void {
    $provider = new PanelFieldTypeCompletionProvider();

    expect($provider->getCompletions(''))->toContain('blocks');
    expect($provider->getCompletions('ema'))->toBe(['email']);
    expect($provider->getCompletions('picker'))->toBe(['filepicker', 'pagepicker', 'userpicker']);
    expect($provider->getCompletions('list'))->toBe(['filelist', 'list', 'pagelist']);
    expect($provider->getCompletions('but'))->toBe(['buttons']);
    expect($provider->getCompletions('pass'))->toBe(['password']);
    expect($provider->getCompletions(''))->toContain('files', 'pages', 'users');
});

it('suggests Kirby Panel section types', function (): void {
    $provider = new PanelSectionTypeCompletionProvider();

    expect($provider->getCompletions(''))->toContain('files');
    expect($provider->getCompletions('fi'))->toBe(['fields', 'files']);
    expect($provider->getCompletions('ile'))->toBe(['files']);
});

it('suggests Kirby hook names', function (): void {
    $provider = new HookNameCompletionProvider();

    expect($provider->getCompletions(''))->toContain('file.changeName:after');
    expect($provider->getCompletions('file-changename-after'))->toBe(['file.changeName:after']);
    expect($provider->getCompletions('system-exception'))->toBe(['system.exception']);
    expect($provider->getCompletions('user-changesecret'))->toBe(['user.changeSecret:after', 'user.changeSecret:before']);
    expect($provider->getCompletions('login-failed'))->toBe(['user.login:failed']);
});

it('suggests Kirby extension names', function (): void {
    $provider = new ExtensionNameCompletionProvider();

    expect($provider->getCompletions(''))->toContain('commands');
    expect($provider->getCompletions('darkroom'))->toBe(['darkroom-drivers']);
    expect($provider->getCompletions('panelview'))->toBe(['panel-view-buttons']);
    expect($provider->getCompletions('authMethods'))->toBe(['auth-methods']);
    expect($provider->getCompletions('structure'))->toBe(['structure-methods', 'structure-object-methods']);
});
