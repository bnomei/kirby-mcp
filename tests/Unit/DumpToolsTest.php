<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Dumps\DumpLogWriter;
use Bnomei\KirbyMcp\Mcp\DumpState;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Tools\DumpTools;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\RequestContext;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;

it('tails dump logs using session trace id when missing', function (): void {
    $projectRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-test-' . bin2hex(random_bytes(8));
    $configDir = $projectRoot . DIRECTORY_SEPARATOR . '.kirby-mcp';
    $logFile = $configDir . DIRECTORY_SEPARATOR . 'dumps.jsonl';

    $originalRoot = getenv(ProjectContext::ENV_PROJECT_ROOT);
    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $projectRoot);

    mkdir($configDir, 0777, true);

    $session = new Session(new InMemorySessionStore(60));
    DumpState::setLastTraceId('trace-a', $session);

    try {
        DumpLogWriter::append([
            'type' => 'dump',
            't' => 1.0,
            'traceId' => 'trace-a',
            'id' => 'a',
            'path' => '/about',
            'values' => ['first'],
        ], $projectRoot);

        DumpLogWriter::append([
            'type' => 'update',
            't' => 1.1,
            'traceId' => 'trace-a',
            'id' => 'a',
            'path' => '/about',
            'set' => ['label' => 'Hello'],
        ], $projectRoot);

        DumpLogWriter::append([
            'type' => 'dump',
            't' => 2.0,
            'traceId' => 'trace-b',
            'id' => 'b',
            'path' => '/about',
            'values' => ['second'],
        ], $projectRoot);

        $tools = new DumpTools();
        $context = new RequestContext($session, new CallToolRequest('kirby_dump_log_tail', []));

        $result = $tools->dumpLogTail(context: $context);

        expect($result)->toBeInstanceOf(CallToolResult::class);

        $payload = $result->structuredContent ?? null;
        expect($payload)->toBeArray();
        expect($payload['traceId'])->toBe('trace-a');
        expect($payload['count'])->toBe(1);

        $event = $payload['events'][0] ?? null;
        expect($event)->toBeArray();
        expect($event['id'])->toBe('a');
        expect($event['label'])->toBe('Hello');
    } finally {
        DumpState::reset($session);

        if (is_file($logFile)) {
            @unlink($logFile);
        }

        if (is_dir($configDir)) {
            @rmdir($configDir);
        }

        if (is_dir($projectRoot)) {
            @rmdir($projectRoot);
        }

        if ($originalRoot === false) {
            putenv(ProjectContext::ENV_PROJECT_ROOT);
        } else {
            putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $originalRoot);
        }
    }
});
