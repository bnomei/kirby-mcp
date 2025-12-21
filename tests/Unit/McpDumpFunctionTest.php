<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Dumps\DumpProjectRootResolver;
use Bnomei\KirbyMcp\Dumps\McpDump;

it('writes a dump entry via mcp_dump', function (): void {
    $projectRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-dump-' . bin2hex(random_bytes(6));
    $logDir = $projectRoot . DIRECTORY_SEPARATOR . '.kirby-mcp';
    $logFile = $logDir . DIRECTORY_SEPARATOR . 'dumps.jsonl';

    if (!is_dir($projectRoot)) {
        mkdir($projectRoot, 0777, true);
    }

    $previousEnv = getenv(DumpProjectRootResolver::ENV_PROJECT_ROOT);
    putenv(DumpProjectRootResolver::ENV_PROJECT_ROOT . '=' . $projectRoot);

    try {
        $dump = mcp_dump('hello');
        expect($dump)->toBeInstanceOf(McpDump::class);

        expect(is_file($logFile))->toBeTrue();
        $contents = file_get_contents($logFile);
        expect($contents)->toBeString()->not()->toBe('');
        expect($contents)->toContain('"type":"dump"');
    } finally {
        if ($previousEnv === false) {
            putenv(DumpProjectRootResolver::ENV_PROJECT_ROOT);
        } else {
            putenv(DumpProjectRootResolver::ENV_PROJECT_ROOT . '=' . $previousEnv);
        }

        if (is_file($logFile)) {
            @unlink($logFile);
        }

        if (is_dir($logDir)) {
            @rmdir($logDir);
        }

        if (is_dir($projectRoot)) {
            @rmdir($projectRoot);
        }
    }
});
