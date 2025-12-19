<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Project\KirbyMcpConfig;

it('reads ide.typeHintScanBytes from config', function (): void {
    KirbyMcpConfig::clearCache();

    $projectRoot = dirname(__DIR__) . '/fixtures/mcp-config-ide';

    $config = KirbyMcpConfig::load($projectRoot);

    expect($config->ideTypeHintScanBytes())->toBe(32768);
});
