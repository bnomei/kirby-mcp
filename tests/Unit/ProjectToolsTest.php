<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Tools\ProjectTools;
use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\RequestContext;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;

it('returns composer audit data via the tool with structured output', function (): void {
    $originalRoot = getenv(ProjectContext::ENV_PROJECT_ROOT);
    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . cmsPath());

    try {
        $tools = new ProjectTools();
        $session = new Session(new InMemorySessionStore(60));
        $context = new RequestContext($session, new CallToolRequest('kirby_composer_audit', []));

        $result = $tools->composerAudit($context);

        expect($result)->toBeInstanceOf(CallToolResult::class);
        if (!$result instanceof CallToolResult) {
            throw new RuntimeException('Expected a CallToolResult instance.');
        }
        expect($result->structuredContent)->toBeArray();
        expect($result->structuredContent['projectRoot'])->toBe(cmsPath());
    } finally {
        if ($originalRoot === false) {
            putenv(ProjectContext::ENV_PROJECT_ROOT);
        } else {
            putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $originalRoot);
        }
    }
});

it('throws when the kirby CLI binary cannot be resolved', function (): void {
    $originalRoot = getenv(ProjectContext::ENV_PROJECT_ROOT);
    $originalBin = getenv(KirbyCliRunner::ENV_KIRBY_BIN);

    $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-project-tools-' . bin2hex(random_bytes(4));
    mkdir($root, 0777, true);

    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $root);
    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=missing-binary');

    try {
        expect(fn () => (new ProjectTools())->kirbyCliVersion())
            ->toThrow(ToolCallException::class, 'Kirby CLI binary not found');
    } finally {
        @rmdir($root);

        if ($originalRoot === false) {
            putenv(ProjectContext::ENV_PROJECT_ROOT);
        } else {
            putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $originalRoot);
        }

        if ($originalBin === false) {
            putenv(KirbyCliRunner::ENV_KIRBY_BIN);
        } else {
            putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $originalBin);
        }
    }
});
