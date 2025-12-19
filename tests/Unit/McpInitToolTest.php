<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\SessionState;
use Bnomei\KirbyMcp\Mcp\Tools\SessionTools;

it('initializes and returns guidance for a composer-based Kirby project', function (): void {
    SessionState::reset();
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $output = (new SessionTools())->init();

    expect($output)->toBeString();
    expect($output)->toContain('<Kirby>');
    expect($output)->toContain('Kirby MCP initialization (tool-first)');
    expect($output)->toContain('Use `kirby://...` resources and resource templates first');
    expect($output)->toContain('## Project Root');
    expect($output)->toContain('`' . cmsPath() . '`');
    expect($output)->toContain('## Environment');
    expect($output)->toContain('## Composer Audit');
    expect($output)->toContain('## Project Info');
    expect($output)->toContain('kirby://prompts');
    expect($output)->toContain('kirby://prompt/{name}');
    expect($output)->toContain('kirby_project_tour');
    expect(SessionState::initCalled())->toBeTrue();
});
