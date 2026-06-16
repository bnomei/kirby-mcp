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
