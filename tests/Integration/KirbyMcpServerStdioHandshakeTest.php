<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

it('boots the MCP stdio server and answers initialize', function (): void {
    $bin = realpath(__DIR__ . '/../../bin/kirby-mcp');
    expect($bin)->not()->toBeFalse();

    $input = implode("\n", [
        json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => new stdClass(),
                'clientInfo' => [
                    'name' => 'tests',
                    'version' => 'dev',
                ],
            ],
        ], JSON_UNESCAPED_SLASHES),
        json_encode([
            'jsonrpc' => '2.0',
            'method' => 'notifications/initialized',
        ], JSON_UNESCAPED_SLASHES),
        json_encode([
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/list',
            'params' => new stdClass(),
        ], JSON_UNESCAPED_SLASHES),
        '',
    ]);

    $process = new Process(
        command: [
            PHP_BINARY,
            '-d',
            'display_errors=0',
            '-d',
            'display_startup_errors=0',
            $bin,
        ],
        cwd: cmsPath(),
        timeout: 15,
    );

    $process->setInput($input);
    $process->run();

    expect($process->getExitCode())->toBe(0);

    $lines = array_values(array_filter(array_map('trim', explode("\n", trim($process->getOutput())))));
    expect($lines)->not()->toBeEmpty();

    foreach ($lines as $line) {
        $decoded = json_decode($line, true);
        expect($decoded)->toBeArray();
    }

    $responses = array_map(
        static fn (string $line): array => json_decode($line, true),
        $lines
    );

    $byId = [];
    foreach ($responses as $response) {
        if (!array_key_exists('id', $response)) {
            continue;
        }
        $byId[(string) $response['id']] = $response;
    }

    expect($byId)->toHaveKey('1');
    expect($byId['1'])->toHaveKey('result');
    expect($byId['1']['result'])->toHaveKey('serverInfo');

    expect($byId)->toHaveKey('2');
    expect($byId['2'])->toHaveKey('result');
    expect($byId['2']['result'])->toHaveKey('tools');
});
