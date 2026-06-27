<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\SessionState;
use Bnomei\KirbyMcp\Mcp\ServerProfile;
use Bnomei\KirbyMcp\Mcp\Tools\SessionTools;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Server\RequestContext;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;

it('initializes and returns guidance for a composer-based Kirby project', function (): void {
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $session = new Session(new InMemorySessionStore(60));
    $context = new RequestContext($session, new CallToolRequest('kirby_init', []));

    $output = (new SessionTools())->init(context: $context);

    expect($output)->toBeString();
    expect($output)->toContain('<Kirby>');
    expect($output)->toContain('Kirby MCP initialization (tool-first)');
    expect($output)->toContain('Use `kirby://...` resources and resource templates first');
    expect($output)->toContain('## Project Root');
    expect($output)->toContain('`' . cmsPath() . '`');
    expect($output)->toContain('## Environment');
    expect($output)->toContain('## Composer Audit');
    expect($output)->toContain('## Project Info');
    expect(SessionState::initCalled($session))->toBeTrue();
});

it('does not mark the session initialized when project init fails validation', function (): void {
    $projectRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-init-' . bin2hex(random_bytes(8));
    mkdir($projectRoot, 0777, true);

    putenv('KIRBY_MCP_PROJECT_ROOT=' . $projectRoot);

    $session = new Session(new InMemorySessionStore(60));
    $context = new RequestContext($session, new CallToolRequest('kirby_init', []));

    // Suppress client-logger notifications (which require a Fiber) so the test
    // exercises only the init gating behaviour on the failure path.
    \Bnomei\KirbyMcp\Mcp\LoggingState::setLevel(\Mcp\Schema\Enum\LoggingLevel::Critical, $session);

    try {
        $threw = false;
        try {
            (new SessionTools())->init(context: $context);
        } catch (\Mcp\Exception\ToolCallException) {
            $threw = true;
        }

        expect($threw)->toBeTrue();
        expect(SessionState::initCalled($session))->toBeFalse();
    } finally {
        putenv('KIRBY_MCP_PROJECT_ROOT');

        if (is_dir($projectRoot)) {
            @rmdir($projectRoot);
        }
    }
});

it('initializes global reference mode without a Kirby project', function (): void {
    putenv('KIRBY_MCP_PROJECT_ROOT');

    $session = new Session(new InMemorySessionStore(60));
    $context = new RequestContext($session, new CallToolRequest('kirby_init', []));

    $output = (new SessionTools(profile: ServerProfile::GLOBAL_REFERENCE))->init(context: $context);

    expect($output)->toBeString();
    expect($output)->toContain('<Kirby>');
    expect($output)->toContain('Kirby MCP initialization (global reference mode)');
    expect($output)->toContain('not connected to any Kirby project');
    expect($output)->toContain('"project": false');
    expect($output)->toContain('"runtime": false');
    expect($output)->toContain('"writes": false');
    expect(SessionState::initCalled($session))->toBeTrue();
});
