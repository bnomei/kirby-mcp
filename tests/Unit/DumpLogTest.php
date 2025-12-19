<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Dumps\DumpLogReader;
use Bnomei\KirbyMcp\Dumps\DumpLogWriter;

it('truncates dumps.jsonl when exceeding dumps.maxBytes', function (): void {
    $projectRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-test-' . bin2hex(random_bytes(8));
    $configDir = $projectRoot . DIRECTORY_SEPARATOR . '.kirby-mcp';
    $configFile = $configDir . DIRECTORY_SEPARATOR . 'mcp.json';
    $logFile = $configDir . DIRECTORY_SEPARATOR . 'dumps.jsonl';

    mkdir($configDir, 0777, true);

    $entry1 = [
        'type' => 'dump',
        't' => 1.0,
        'traceId' => 't1',
        'id' => 'a',
        'path' => '/about',
        'values' => [str_repeat('a', 80)],
    ];

    $entry2 = [
        'type' => 'dump',
        't' => 2.0,
        'traceId' => 't1',
        'id' => 'b',
        'path' => '/about',
        'values' => [str_repeat('b', 80)],
    ];

    $line1 = json_encode($entry1, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) . "\n";
    $line2 = json_encode($entry2, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) . "\n";

    $maxBytes = strlen($line1) + strlen($line2) - 1;
    file_put_contents($configFile, json_encode([
        'dumps' => [
            'maxBytes' => $maxBytes,
        ],
    ], JSON_THROW_ON_ERROR));

    try {
        DumpLogWriter::append($entry1, $projectRoot);
        DumpLogWriter::append($entry2, $projectRoot);

        $contents = file_get_contents($logFile);
        expect($contents)->toBeString();
        expect($contents)->toBe($line2);
    } finally {
        if (is_file($logFile)) {
            @unlink($logFile);
        }

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

it('keeps the newest half of lines when compacting', function (): void {
    $projectRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-test-' . bin2hex(random_bytes(8));
    $configDir = $projectRoot . DIRECTORY_SEPARATOR . '.kirby-mcp';
    $configFile = $configDir . DIRECTORY_SEPARATOR . 'mcp.json';
    $logFile = $configDir . DIRECTORY_SEPARATOR . 'dumps.jsonl';

    mkdir($configDir, 0777, true);

    $entries = [];
    for ($i = 1; $i <= 5; $i++) {
        $entries[] = [
            'type' => 'dump',
            't' => (float) $i,
            'traceId' => 't1',
            'id' => (string) $i,
            'path' => '/about',
            'values' => ["line-{$i}"],
        ];
    }

    $lines = array_map(
        static fn (array $entry): string => json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) . "\n",
        $entries,
    );

    $maxBytes = array_sum(array_map('strlen', $lines)) - 1;
    file_put_contents($configFile, json_encode([
        'dumps' => [
            'maxBytes' => $maxBytes,
        ],
    ], JSON_THROW_ON_ERROR));

    try {
        DumpLogWriter::append($entries[0], $projectRoot);
        DumpLogWriter::append($entries[1], $projectRoot);
        DumpLogWriter::append($entries[2], $projectRoot);
        DumpLogWriter::append($entries[3], $projectRoot);

        // This append triggers compaction (4 existing lines + 1 new line exceeds maxBytes).
        DumpLogWriter::append($entries[4], $projectRoot);

        $contents = file_get_contents($logFile);
        expect($contents)->toBeString();

        // Keep newest half of existing (2 of 4) + new line = lines 3, 4, 5.
        expect($contents)->toBe($lines[2] . $lines[3] . $lines[4]);
    } finally {
        if (is_file($logFile)) {
            @unlink($logFile);
        }

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

it('can disable mcp_dump log writes via dumps.enabled=false', function (): void {
    $projectRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-test-' . bin2hex(random_bytes(8));
    $configDir = $projectRoot . DIRECTORY_SEPARATOR . '.kirby-mcp';
    $configFile = $configDir . DIRECTORY_SEPARATOR . 'mcp.json';
    $logFile = $configDir . DIRECTORY_SEPARATOR . 'dumps.jsonl';

    mkdir($configDir, 0777, true);
    file_put_contents($configFile, json_encode([
        'dumps' => [
            'enabled' => false,
        ],
    ], JSON_THROW_ON_ERROR));

    try {
        DumpLogWriter::append([
            'type' => 'dump',
            't' => 1.0,
            'traceId' => 't1',
            'id' => 'a',
            'path' => '/about',
            'values' => ['hello'],
        ], $projectRoot);

        expect(is_file($logFile))->toBeFalse();
    } finally {
        if (is_file($logFile)) {
            @unlink($logFile);
        }

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

it('tails and merges dump updates by id', function (): void {
    $projectRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-test-' . bin2hex(random_bytes(8));
    $configDir = $projectRoot . DIRECTORY_SEPARATOR . '.kirby-mcp';
    $logFile = $configDir . DIRECTORY_SEPARATOR . 'dumps.jsonl';

    mkdir($configDir, 0777, true);

    $lines = [
        json_encode([
            'type' => 'dump',
            't' => 1.0,
            'traceId' => 't1',
            'id' => 'a',
            'path' => '/about',
            'values' => ['hello'],
        ], JSON_THROW_ON_ERROR),
        json_encode([
            'type' => 'update',
            't' => 1.1,
            'traceId' => 't1',
            'id' => 'a',
            'path' => '/about',
            'set' => ['color' => 'green'],
        ], JSON_THROW_ON_ERROR),
        json_encode([
            'type' => 'dump',
            't' => 2.0,
            'traceId' => 't1',
            'id' => 'b',
            'path' => '/about',
            'values' => ['world'],
        ], JSON_THROW_ON_ERROR),
        json_encode([
            'type' => 'dump',
            't' => 3.0,
            'traceId' => 't2',
            'id' => 'c',
            'path' => '/contact',
            'values' => ['skip'],
        ], JSON_THROW_ON_ERROR),
    ];

    file_put_contents($logFile, implode("\n", $lines) . "\n");

    try {
        $events = DumpLogReader::tail(
            projectRoot: $projectRoot,
            traceId: 't1',
            path: '/about',
            limit: 0,
        );

        expect($events)->toHaveCount(2);
        expect($events[0]['id'] ?? null)->toBe('a');
        expect($events[0]['color'] ?? null)->toBe('green');
        expect($events[1]['id'] ?? null)->toBe('b');
    } finally {
        if (is_file($logFile)) {
            @unlink($logFile);
        }

        if (is_dir($configDir)) {
            @rmdir($configDir);
        }

        if (is_dir($projectRoot)) {
            @rmdir($projectRoot);
        }
    }
});
