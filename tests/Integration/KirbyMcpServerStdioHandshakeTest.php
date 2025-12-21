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
        json_encode([
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'resources/list',
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

    $serverInfo = $byId['1']['result']['serverInfo'];
    $composerJson = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'composer.json';
    $expectedVersion = '0.0.0';
    if (is_file($composerJson)) {
        $contents = file_get_contents($composerJson);
        if (is_string($contents) && trim($contents) !== '') {
            $decoded = json_decode($contents, true);
            if (is_array($decoded)) {
                $version = $decoded['version'] ?? null;
                if (is_string($version) && trim($version) !== '') {
                    $expectedVersion = trim($version);
                }
            }
        }
    }

    expect($serverInfo)->toHaveKey('version');
    expect($serverInfo['version'])->toBeString();
    expect(str_starts_with($serverInfo['version'], $expectedVersion))->toBeTrue();

    expect($byId)->toHaveKey('2');
    expect($byId['2'])->toHaveKey('result');
    expect($byId['2']['result'])->toHaveKey('tools');

    $tools = $byId['2']['result']['tools'];
    expect($tools)->toBeArray();

    $byName = [];
    foreach ($tools as $tool) {
        if (!is_array($tool)) {
            continue;
        }
        $name = $tool['name'] ?? null;
        if (is_string($name) && $name !== '') {
            $byName[$name] = $tool;
        }
    }

    foreach (['kirby_info', 'kirby_read_page_content'] as $toolName) {
        expect($byName)->toHaveKey($toolName);
        $meta = $byName[$toolName]['_meta'] ?? null;
        expect($meta)->toBeArray();
        expect($meta)->toHaveKey('outputSchema');
        expect($meta['outputSchema'])->toBeArray();
    }

    expect($byId)->toHaveKey('3');
    expect($byId['3'])->toHaveKey('result');
    expect($byId['3']['result'])->toHaveKey('resources');

    $resources = $byId['3']['result']['resources'];
    expect($resources)->toBeArray();

    $byUri = [];
    foreach ($resources as $resource) {
        if (!is_array($resource)) {
            continue;
        }

        $uri = $resource['uri'] ?? null;
        if (is_string($uri) && $uri !== '') {
            $byUri[$uri] = $resource;
        }
    }

    expect($byUri)->toHaveKey('kirby://glossary');
    $glossary = $byUri['kirby://glossary'];
    expect($glossary)->toHaveKey('annotations');
    expect($glossary['annotations'])->toBeArray();
    expect($glossary['annotations'])->toHaveKey('audience');
    expect($glossary['annotations'])->toHaveKey('priority');
    expect($glossary)->toHaveKey('size');
    expect($glossary['size'])->toBeInt();
    $meta = $glossary['_meta'] ?? null;
    expect($meta)->toBeArray();
    expect($meta)->toHaveKey('lastModified');
});
