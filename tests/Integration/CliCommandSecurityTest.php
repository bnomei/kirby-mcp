<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Resources\CliResources;
use Bnomei\KirbyMcp\Mcp\Tools\CliTools;
use Mcp\Exception\ResourceReadException;
use Mcp\Exception\ToolCallException;

beforeEach(function (): void {
    $this->cliSecurityRoot = sys_get_temp_dir() . '/kirby-cli-security-' . bin2hex(random_bytes(8));
    mkdir($this->cliSecurityRoot . '/commands', 0777, true);
    mkdir($this->cliSecurityRoot . '/.kirby-mcp');
    file_put_contents($this->cliSecurityRoot . '/index.php', '<?php require ' . var_export(realpath(__DIR__ . '/../../vendor/autoload.php'), true) . '; new Kirby\Cms\App(["roots" => ["index" => __DIR__, "commands" => __DIR__ . "/commands"]]);');
    $this->cliSecurityEnv = [
        'KIRBY_MCP_PROJECT_ROOT' => getenv('KIRBY_MCP_PROJECT_ROOT'),
        KirbyCliRunner::ENV_KIRBY_BIN => getenv(KirbyCliRunner::ENV_KIRBY_BIN),
    ];
    putenv('KIRBY_MCP_PROJECT_ROOT=' . $this->cliSecurityRoot);
    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . realpath(__DIR__ . '/../../vendor/bin/kirby'));
    file_put_contents($this->cliSecurityRoot . '/.kirby-mcp/mcp.json', json_encode([
        'cli' => ['allow' => ['*'], 'deny' => ['license:info']],
    ], JSON_THROW_ON_ERROR));
    file_put_contents($this->cliSecurityRoot . '/probe.php', '<?php file_put_contents(__DIR__ . "/loaded", "loaded"); return ["command" => static function () {}];');
});

afterEach(function (): void {
    foreach ($this->cliSecurityEnv as $key => $value) {
        putenv($value === false ? $key : $key . '=' . $value);
    }
    foreach (['index.php', 'probe.php', 'loaded', '.kirby-mcp/mcp.json'] as $path) {
        if (is_file($this->cliSecurityRoot . '/' . $path)) {
            unlink($this->cliSecurityRoot . '/' . $path);
        }
    }
    rmdir($this->cliSecurityRoot . '/.kirby-mcp');
    rmdir($this->cliSecurityRoot . '/commands');
    rmdir($this->cliSecurityRoot);
});

it('rejects command help traversal before loading out-of-root PHP', function (string $command): void {
    $error = null;
    try {
        (new CliResources())->command($command);
    } catch (ResourceReadException $exception) {
        $error = $exception;
    }
    expect(file_exists($this->cliSecurityRoot . '/loaded'))->toBeFalse();
    expect($error)->toBeInstanceOf(ResourceReadException::class);
})->with(['../probe', '..:probe', '%2e%2e%2fprobe', '%252e%252e%252fprobe']);

it('rejects alternate dedicated and denied command spellings despite wildcard allow', function (string $command): void {
    expect(fn () => (new CliTools())->runCliCommand($command, allowWrite: true))
        ->toThrow(ToolCallException::class, 'canonical');
})->with(['mcp/eval', 'mcp/query/dot', 'mcp/native', 'license/info', 'license::info', 'license%3ainfo', 'license\\info']);

it('keeps canonical help and dedicated or denied commands working as intended', function (): void {
    $help = (new CliResources())->command('help');
    expect($help['ok'])->toBeTrue();
    expect((new CliResources())->command('make:template')['ok'])->toBeTrue();
    $cli = new CliTools();
    expect($cli->runCliCommand('version')['ok'])->toBeTrue();
    expect($cli->runCliCommand('mcp:eval')['message'])->toContain('kirby_eval');
    expect($cli->runCliCommand('mcp:native')['message'])->toContain('kirby_upload_file');
    expect($cli->runCliCommand('license:info')['policy']['matchedDeny'])->toBe('license:info');
});
