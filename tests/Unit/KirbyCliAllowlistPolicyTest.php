<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Policies\KirbyCliAllowlistPolicy;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;

it('denies commands even if allowlisted when deny matches', function (): void {
    $projectRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-test-' . bin2hex(random_bytes(8));
    $configDir = $projectRoot . DIRECTORY_SEPARATOR . '.kirby-mcp';
    $configFile = $configDir . DIRECTORY_SEPARATOR . 'mcp.json';

    mkdir($configDir, 0777, true);
    file_put_contents($configFile, json_encode([
        'cli' => [
            'allow' => ['version'],
            'deny' => ['version'],
        ],
    ], JSON_THROW_ON_ERROR));

    try {
        $config = KirbyMcpConfig::load($projectRoot);
        $policy = new KirbyCliAllowlistPolicy($config, defaultAllow: [], defaultAllowWrite: []);

        $decision = $policy->evaluate('version', allowWrite: false);

        expect($decision->allowed)->toBeFalse();
        expect($decision->matchedDeny)->toBe('version');
    } finally {
        if (is_file($configFile)) {
            @unlink($configFile);
        }

        if (is_dir($configDir)) {
            @rmdir($configDir);
        }

        if (is_dir($projectRoot)) {
            @rmdir($projectRoot);
        }
    }
});

it('requires allowWrite=true for write-capable allowWrite patterns', function (): void {
    $projectRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-test-' . bin2hex(random_bytes(8));
    $configDir = $projectRoot . DIRECTORY_SEPARATOR . '.kirby-mcp';
    $configFile = $configDir . DIRECTORY_SEPARATOR . 'mcp.json';

    mkdir($configDir, 0777, true);
    file_put_contents($configFile, json_encode([
        'cli' => [
            'allowWrite' => ['make:*'],
        ],
    ], JSON_THROW_ON_ERROR));

    try {
        $config = KirbyMcpConfig::load($projectRoot);
        $policy = new KirbyCliAllowlistPolicy($config, defaultAllow: [], defaultAllowWrite: []);

        $decision = $policy->evaluate('make:template', allowWrite: false);

        expect($decision->allowed)->toBeFalse();
        expect($decision->matchedAllowWrite)->toBe('make:*');
        expect($decision->requiresAllowWrite())->toBeTrue();

        $allowed = $policy->evaluate('make:template', allowWrite: true);
        expect($allowed->allowed)->toBeTrue();
    } finally {
        if (is_file($configFile)) {
            @unlink($configFile);
        }

        if (is_dir($configDir)) {
            @rmdir($configDir);
        }

        if (is_dir($projectRoot)) {
            @rmdir($projectRoot);
        }
    }
});
