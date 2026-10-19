<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliCommand;
use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Mcp\Policies\KirbyCliAllowlistPolicy;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;

it('accepts canonical built-in and custom command names', function (string $command): void {
    KirbyCliCommand::validate($command);
    expect(KirbyCliCommand::isCanonical($command))->toBeTrue();
})->with(['help', 'version', 'uuid:generate', 'make:blueprint', 'mcp:query:dot', 'my-plugin:custom_command', 'Plugin2:_task-1']);

it('rejects noncanonical names before policy matching or binary resolution', function (string $command): void {
    $root = sys_get_temp_dir() . '/kirby-cli-no-project';
    $policy = new KirbyCliAllowlistPolicy(KirbyMcpConfig::load($root), defaultAllow: ['*']);
    expect(fn () => $policy->evaluate($command, true))->toThrow(InvalidArgumentException::class, 'canonical');
    expect(fn () => (new KirbyCliRunner())->run($root, [$command, '--help']))->toThrow(InvalidArgumentException::class, 'canonical');
})->with(['', ' ', ' help', "help\n", 'foo bar', '../probe', '..:probe', 'mcp/eval', 'mcp\\eval', ':help', 'help:', 'mcp::eval', '%6dcp:eval', 'mcp%3aeval', '%252e%252e:probe', '-help', '--help', "help\0", 'héⅼp']);
