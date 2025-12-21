<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Dumps\DumpLogReader;
use Bnomei\KirbyMcp\Dumps\McpDump;
use Bnomei\KirbyMcp\Dumps\McpDumpContext;

it('writes dump entries and applies updates', function (): void {
    $projectRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-test-' . bin2hex(random_bytes(8));
    $configDir = $projectRoot . DIRECTORY_SEPARATOR . '.kirby-mcp';
    $logFile = $configDir . DIRECTORY_SEPARATOR . 'dumps.jsonl';

    $originalTrace = getenv(McpDumpContext::ENV_TRACE_ID);
    putenv(McpDumpContext::ENV_TRACE_ID . '=trace-test');

    mkdir($configDir, 0777, true);

    try {
        $dump = McpDump::create(
            values: ['hello'],
            backtrace: [[
                'file' => '/tmp/example.php',
                'line' => 12,
                'function' => 'test',
                'class' => 'Example',
                'type' => '::',
            ]],
            projectRoot: $projectRoot,
        );

        $dump->label('Greeting')->red();
        $dump->pass('world');

        $events = DumpLogReader::tail(projectRoot: $projectRoot, traceId: 'trace-test');

        expect($events)->toHaveCount(1);

        $event = $events[0];
        expect($event['traceId'])->toBe('trace-test');
        expect($event['values'])->toBe(['world']);
        expect($event['label'])->toBe('Greeting');
        expect($event['color'])->toBe('red');
        expect($event['origin'])->toBeArray();
        expect($event['origin']['file'] ?? null)->toBe('/tmp/example.php');
    } finally {
        McpDumpContext::reset();

        if (is_file($logFile)) {
            @unlink($logFile);
        }

        if (is_dir($configDir)) {
            @rmdir($configDir);
        }

        if (is_dir($projectRoot)) {
            @rmdir($projectRoot);
        }

        if ($originalTrace === false) {
            putenv(McpDumpContext::ENV_TRACE_ID);
        } else {
            putenv(McpDumpContext::ENV_TRACE_ID . '=' . $originalTrace);
        }
    }
});
