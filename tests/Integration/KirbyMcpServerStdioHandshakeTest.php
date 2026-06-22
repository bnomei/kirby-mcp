<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;
use Composer\InstalledVersions;

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
        json_encode([
            'jsonrpc' => '2.0',
            'id' => 4,
            'method' => 'resources/read',
            'params' => [
                'uri' => 'kirby://glossary',
            ],
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
    $composerVersion = null;
    $expectedVersion = null;
    if (is_file($composerJson)) {
        $contents = file_get_contents($composerJson);
        if (is_string($contents) && trim($contents) !== '') {
            $decoded = json_decode($contents, true);
            if (is_array($decoded)) {
                $version = $decoded['version'] ?? null;
                if (is_string($version) && trim($version) !== '') {
                    $composerVersion = trim($version);
                }
            }
        }
    }

    if (class_exists(InstalledVersions::class)) {
        try {
            if (InstalledVersions::isInstalled('bnomei/kirby-mcp')) {
                $pretty = InstalledVersions::getPrettyVersion('bnomei/kirby-mcp');
                $reference = InstalledVersions::getReference('bnomei/kirby-mcp');

                if (is_string($pretty) && $pretty !== '') {
                    $isDev = str_starts_with($pretty, 'dev-') || $pretty === 'dev-main' || $pretty === 'dev-master';
                    if ($isDev && is_string($composerVersion) && $composerVersion !== '') {
                        $expectedVersion = $composerVersion;
                    } else {
                        $expectedVersion = $pretty;
                    }

                    if (is_string($reference) && $reference !== '') {
                        $expectedVersion .= '+' . substr($reference, 0, 7);
                    }
                }
            }
        } catch (Throwable) {
            // Fall through to the same file-based fallback used by the server factory.
        }
    }

    $expectedVersion ??= $composerVersion ?? '0.0.0';

    expect($serverInfo)->toHaveKey('version');
    expect($serverInfo['version'])->toBeString();
    expect($serverInfo['version'])->toBe($expectedVersion);

    $capabilities = $byId['1']['result']['capabilities'] ?? null;
    expect($capabilities)->toBeArray();
    expect($capabilities)->toHaveKey('resources');
    expect($capabilities['resources'])->toBeArray();
    expect($capabilities['resources']['subscribe'] ?? null)->toBeTrue();

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
        $tool = $byName[$toolName];
        $outputSchema = $tool['outputSchema'] ?? null;
        expect($outputSchema)->toBeArray();
        expect($outputSchema)->toHaveKey('type');
        expect($outputSchema['type'])->toBe('object');
    }

    foreach ([
        'kirby_update_page_content',
        'kirby_update_site_content',
        'kirby_update_file_content',
        'kirby_update_user_content',
    ] as $toolName) {
        expect($byName)->toHaveKey($toolName);
        $tool = $byName[$toolName];
        $inputSchema = $tool['inputSchema'] ?? null;
        expect($inputSchema)->toBeArray();
        $dataType = $inputSchema['properties']['data']['type'] ?? null;
        expect($dataType)->toBeArray();
        expect($dataType)->toContain('object');
        expect($dataType)->toContain('string');
        expect($inputSchema['properties']['data']['additionalProperties'] ?? null)->toBeTrue();
    }

    $arraySchemaViolations = [];
    foreach ($byName as $toolName => $tool) {
        $inputSchema = $tool['inputSchema'] ?? null;
        if (!is_array($inputSchema)) {
            continue;
        }

        collectArraySchemasWithoutItems($inputSchema, '#', $toolName, $arraySchemaViolations);
    }

    expect($arraySchemaViolations)->toBe([]);

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

        expectCodexSafeResourceDescriptor($resource);

        $uri = $resource['uri'] ?? null;
        if (is_string($uri) && $uri !== '') {
            $byUri[$uri] = $resource;
        }
    }

    expect($byUri)->toHaveKey('kirby://glossary');
    $glossary = $byUri['kirby://glossary'];
    expect($glossary['title'] ?? null)->toBe('Kirby Glossary');
    expect($glossary['mimeType'] ?? null)->toBe('text/markdown');

    expect($byId)->toHaveKey('4');
    expect($byId['4'])->toHaveKey('result');
    $meta = $byId['4']['result']['contents'][0]['_meta'] ?? null;
    expect($meta)->toBeArray();
    expect($meta)->toHaveKey('lastModified');
});

it('boots the global reference MCP stdio server without project tools', function (): void {
    $bin = realpath(__DIR__ . '/../../bin/kirby-mcp');
    expect($bin)->not()->toBeFalse();

    $cwd = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-global-reference-' . bin2hex(random_bytes(6));
    mkdir($cwd, 0777, true);

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
        json_encode([
            'jsonrpc' => '2.0',
            'id' => 4,
            'method' => 'resources/templates/list',
            'params' => new stdClass(),
        ], JSON_UNESCAPED_SLASHES),
        json_encode([
            'jsonrpc' => '2.0',
            'id' => 5,
            'method' => 'tools/call',
            'params' => [
                'name' => 'kirby_init',
                'arguments' => new stdClass(),
            ],
        ], JSON_UNESCAPED_SLASHES),
        json_encode([
            'jsonrpc' => '2.0',
            'id' => 6,
            'method' => 'resources/read',
            'params' => [
                'uri' => 'kirby://tools',
            ],
        ], JSON_UNESCAPED_SLASHES),
        json_encode([
            'jsonrpc' => '2.0',
            'id' => 7,
            'method' => 'tools/call',
            'params' => [
                'name' => 'kirby_tool_suggest',
                'arguments' => [
                    'query' => 'render update roots page content',
                    'limit' => 20,
                ],
            ],
        ], JSON_UNESCAPED_SLASHES),
        '',
    ]);

    try {
        $process = new Process(
            command: [
                PHP_BINARY,
                '-d',
                'display_errors=0',
                '-d',
                'display_startup_errors=0',
                $bin,
                '--global',
            ],
            cwd: $cwd,
            env: [
                'KIRBY_MCP_PROJECT_ROOT' => false,
            ],
            timeout: 15,
        );

        $process->setInput($input);
        $process->run();
    } finally {
        @rmdir($cwd);
    }

    expect($process->getExitCode())->toBe(0);

    $lines = array_values(array_filter(array_map('trim', explode("\n", trim($process->getOutput())))));
    expect($lines)->not()->toBeEmpty();

    $byId = [];
    foreach ($lines as $line) {
        $decoded = json_decode($line, true);
        expect($decoded)->toBeArray();
        if (array_key_exists('id', $decoded)) {
            $byId[(string) $decoded['id']] = $decoded;
        }
    }

    expect($byId['1']['result']['serverInfo']['name'] ?? null)->toBe('Kirby MCP Reference');
    expect($byId['1']['result']['instructions'] ?? '')->toContain('global Kirby reference MCP');

    $tools = $byId['2']['result']['tools'] ?? [];
    $toolNames = array_values(array_filter(array_map(
        static fn (array $tool): ?string => is_string($tool['name'] ?? null) ? $tool['name'] : null,
        $tools,
    )));
    sort($toolNames);
    expect($toolNames)->toBe([
        'kirby_init',
        'kirby_online',
        'kirby_online_plugins',
        'kirby_search',
        'kirby_tool_suggest',
    ]);
    expect($toolNames)->not()->toContain('kirby_info');
    expect($toolNames)->not()->toContain('kirby_read_page_content');
    expect($toolNames)->not()->toContain('kirby_runtime_install');
    expect($toolNames)->not()->toContain('kirby_update_page_content');

    $resources = $byId['3']['result']['resources'] ?? [];
    $resourceUris = array_values(array_filter(array_map(
        static function (array $resource): ?string {
            expectCodexSafeResourceDescriptor($resource);

            return is_string($resource['uri'] ?? null) ? $resource['uri'] : null;
        },
        $resources,
    )));
    sort($resourceUris);
    expect($resourceUris)->toBe([
        'kirby://blueprints/update-schema',
        'kirby://extensions',
        'kirby://fields',
        'kirby://fields/update-schema',
        'kirby://glossary',
        'kirby://hooks',
        'kirby://kb',
        'kirby://sections',
        'kirby://tools',
    ]);
    expect($resourceUris)->not()->toContain('kirby://commands');
    expect($resourceUris)->not()->toContain('kirby://info');
    expect($resourceUris)->not()->toContain('kirby://roots');

    $templates = $byId['4']['result']['resourceTemplates'] ?? [];
    $templateUris = array_values(array_filter(array_map(
        static fn (array $template): ?string => is_string($template['uriTemplate'] ?? null) ? $template['uriTemplate'] : null,
        $templates,
    )));
    sort($templateUris);
    expect($templateUris)->toBe([
        'kirby://blueprint/{type}/update-schema',
        'kirby://extension/{name}',
        'kirby://field/{type}',
        'kirby://field/{type}/update-schema',
        'kirby://glossary/{term}',
        'kirby://hook/{name}',
        'kirby://kb/{path}',
        'kirby://section/{type}',
    ]);
    expect($templateUris)->not()->toContain('kirby://page/content/{encodedIdOrUuid}');

    $initText = $byId['5']['result']['content'][0]['text'] ?? '';
    expect($initText)->toBeString();
    expect($initText)->toContain('global reference mode');
    expect($initText)->toContain('not connected to any Kirby project');
    expect($initText)->toContain('"projectRoot": null');
    expect($initText)->not()->toContain('Composer Audit');
    expect($initText)->not()->toContain('missing getkirby/cms');
    expect($byId['5']['result']['isError'] ?? false)->not()->toBeTrue();

    $toolIndexText = $byId['6']['result']['contents'][0]['text'] ?? '';
    expect($toolIndexText)->toBeString();
    $toolIndex = json_decode($toolIndexText, true);
    expect($toolIndex)->toBeArray();
    $toolIndexItems = is_array($toolIndex) ? $toolIndex : [];
    $toolIndexNames = array_values(array_filter(array_map(
        static fn (mixed $item): ?string => is_array($item) && is_string($item['name'] ?? null)
            ? $item['name']
            : null,
        $toolIndexItems,
    )));
    expect($toolIndexNames)->toContain('kirby_search');
    expect($toolIndexNames)->toContain('kirby://field/{type}');
    expect($toolIndexNames)->not()->toContain('kirby_render_page');
    expect($toolIndexNames)->not()->toContain('kirby_roots');
    expect($toolIndexNames)->not()->toContain('kirby_update_page_content');

    $suggestions = $byId['7']['result']['structuredContent']['suggestions'] ?? [];
    expect($suggestions)->toBeArray();
    $suggestedNames = array_values(array_filter(array_map(
        static fn (array $suggestion): ?string => is_string($suggestion['name'] ?? null) ? $suggestion['name'] : null,
        $suggestions,
    )));
    expect($suggestedNames)->not()->toContain('kirby_render_page');
    expect($suggestedNames)->not()->toContain('kirby_roots');
    expect($suggestedNames)->not()->toContain('kirby_update_page_content');
});

/**
 * @param array<string, mixed> $schema
 * @param array<int, string> $violations
 */
function collectArraySchemasWithoutItems(array $schema, string $path, string $toolName, array &$violations): void
{
    $type = $schema['type'] ?? null;
    $isArraySchema = $type === 'array' || (is_array($type) && in_array('array', $type, true));

    if ($isArraySchema && !array_key_exists('items', $schema)) {
        $violations[] = $toolName . ' ' . $path;
    }

    foreach ($schema as $key => $value) {
        if (!is_array($value)) {
            continue;
        }

        collectArraySchemasWithoutItems(
            $value,
            $path . '/' . (is_int($key) ? (string) $key : $key),
            $toolName,
            $violations,
        );
    }
}
