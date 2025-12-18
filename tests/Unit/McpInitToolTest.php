<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\SessionState;
use Bnomei\KirbyMcp\Mcp\Tools\SessionTools;

it('initializes and returns guidance for a composer-based Kirby project', function (): void {
    SessionState::reset();
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());

    $data = (new SessionTools())->init();

    expect($data)->toHaveKey('initialized', true);
    expect($data)->toHaveKey('instructions');
    expect($data['instructions'])->toContain('CLI-first');
    expect($data['recommendedNextTools'])->toContain('kirby_project_info');
    expect(SessionState::initCalled())->toBeTrue();
});
