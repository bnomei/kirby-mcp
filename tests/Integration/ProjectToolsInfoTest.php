<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Tools\ProjectTools;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\RequestContext;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;

it('returns project info via the tool with structured output', function (): void {
    $originalRoot = getenv(ProjectContext::ENV_PROJECT_ROOT);
    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . cmsPath());

    try {
        $tools = new ProjectTools();
        $session = new Session(new InMemorySessionStore(60));
        $context = new RequestContext($session, new CallToolRequest('kirby_info', []));

        $result = $tools->projectInfo($context);

        expect($result)->toBeInstanceOf(CallToolResult::class);
        if (!$result instanceof CallToolResult) {
            throw new RuntimeException('Expected a CallToolResult instance.');
        }
        expect($result->structuredContent)->toBeArray();
        expect($result->structuredContent['projectRoot'])->toBe(cmsPath());
        expect($result->structuredContent['kirbyVersion'])->toBeString()->not()->toBe('');
    } finally {
        if ($originalRoot === false) {
            putenv(ProjectContext::ENV_PROJECT_ROOT);
        } else {
            putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $originalRoot);
        }
    }
});

it('returns kirby CLI version via the tool', function (): void {
    $originalRoot = getenv(ProjectContext::ENV_PROJECT_ROOT);
    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . cmsPath());

    try {
        $tools = new ProjectTools();
        $session = new Session(new InMemorySessionStore(60));
        $context = new RequestContext($session, new CallToolRequest('kirby_cli_version', []));

        $result = $tools->kirbyCliVersion($context);

        expect($result)->toBeInstanceOf(CallToolResult::class);
        if (!$result instanceof CallToolResult) {
            throw new RuntimeException('Expected a CallToolResult instance.');
        }
        expect($result->structuredContent)->toBeArray();
        expect($result->structuredContent['exitCode'])->toBe(0);
        expect($result->structuredContent['stdout'])->toBeString()->not()->toBe('');
    } finally {
        if ($originalRoot === false) {
            putenv(ProjectContext::ENV_PROJECT_ROOT);
        } else {
            putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $originalRoot);
        }
    }
});
