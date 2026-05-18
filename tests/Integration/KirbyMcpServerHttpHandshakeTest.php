<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\HttpMcpTracer;
use Bnomei\KirbyMcp\Mcp\ServerFactory;
use GuzzleHttp\Psr7\HttpFactory;
use Mcp\Server\Session\FileSessionStore;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Process\Process;

function kirbyMcpHttpJsonRequest(string $method, int|string|null $id = null): string
{
    $payload = [
        'jsonrpc' => '2.0',
        'method' => $method,
    ];

    if ($id !== null) {
        $payload['id'] = $id;
    }

    if ($method === 'initialize') {
        $payload['params'] = [
            'protocolVersion' => '2024-11-05',
            'capabilities' => new stdClass(),
            'clientInfo' => [
                'name' => 'tests',
                'version' => 'dev',
            ],
        ];
    } else {
        $payload['params'] = new stdClass();
    }

    return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
}

/**
 * @return array<string, mixed>
 */
function kirbyMcpHttpDecodeResponse(ResponseInterface $response): array
{
    $decoded = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    expect($decoded)->toBeArray();

    return $decoded;
}

/**
 * @param array<int, array<string, mixed>> $responses
 *
 * @return array<string, mixed>
 */
function kirbyMcpJsonRpcResponseById(array $responses, int $id): array
{
    foreach ($responses as $response) {
        if (($response['id'] ?? null) === $id) {
            return $response;
        }
    }

    return [];
}

/**
 * @return array{tools: array<string, true>, resources: array<string, true>}
 */
function kirbyMcpStdioSurface(): array
{
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
    $responses = array_map(
        static fn (string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR),
        $lines
    );

    $tools = [];
    $toolsResponse = kirbyMcpJsonRpcResponseById($responses, 2);
    $toolsResult = $toolsResponse['result']['tools'] ?? null;
    if (is_array($toolsResult)) {
        foreach ($toolsResult as $tool) {
            if (is_array($tool) && is_string($tool['name'] ?? null)) {
                $tools[$tool['name']] = true;
            }
        }
    }

    $resources = [];
    $resourcesResponse = kirbyMcpJsonRpcResponseById($responses, 3);
    $resourcesResult = $resourcesResponse['result']['resources'] ?? null;
    if (is_array($resourcesResult)) {
        foreach ($resourcesResult as $resource) {
            if (is_array($resource) && is_string($resource['uri'] ?? null)) {
                $resources[$resource['uri']] = true;
            }
        }
    }

    return [
        'tools' => $tools,
        'resources' => $resources,
    ];
}

it('traces the MCP server over a single /mcp HTTP endpoint with reusable session state', function (): void {
    $factory = new HttpFactory();
    $sessionDir = sys_get_temp_dir() . '/kirby-mcp-http-test-' . bin2hex(random_bytes(6));
    $sessionStore = new FileSessionStore($sessionDir);
    $tracer = new HttpMcpTracer(new ServerFactory(), $sessionStore);

    $initializeRequest = $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
        ->withHeader('Content-Type', 'application/json')
        ->withBody($factory->createStream(kirbyMcpHttpJsonRequest('initialize', 1)));

    $initializeResponse = $tracer->handle($initializeRequest);
    expect($initializeResponse->getStatusCode())->toBe(200);
    expect($initializeResponse->getHeaderLine('Mcp-Session-Id'))->not()->toBe('');

    $sessionId = $initializeResponse->getHeaderLine('Mcp-Session-Id');
    $initializePayload = kirbyMcpHttpDecodeResponse($initializeResponse);
    expect($initializePayload['result']['serverInfo']['name'] ?? null)->toBe('Kirby MCP');

    $initializedRequest = $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('Mcp-Session-Id', $sessionId)
        ->withBody($factory->createStream(kirbyMcpHttpJsonRequest('notifications/initialized')));
    expect($tracer->handle($initializedRequest)->getStatusCode())->toBe(202);

    $toolsRequest = $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('Mcp-Session-Id', $sessionId)
        ->withBody($factory->createStream(kirbyMcpHttpJsonRequest('tools/list', 2)));
    $toolsResponse = $tracer->handle($toolsRequest);
    expect($toolsResponse->getStatusCode())->toBe(200);
    expect($toolsResponse->getHeaderLine('Mcp-Session-Id'))->toBe($sessionId);
    $toolsPayload = kirbyMcpHttpDecodeResponse($toolsResponse);

    $resourcesRequest = $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('Mcp-Session-Id', $sessionId)
        ->withBody($factory->createStream(kirbyMcpHttpJsonRequest('resources/list', 3)));
    $resourcesResponse = $tracer->handle($resourcesRequest);
    expect($resourcesResponse->getStatusCode())->toBe(200);
    expect($resourcesResponse->getHeaderLine('Mcp-Session-Id'))->toBe($sessionId);
    $resourcesPayload = kirbyMcpHttpDecodeResponse($resourcesResponse);

    $getResponse = $tracer->handle(
        $factory->createServerRequest('GET', 'http://127.0.0.1/mcp')
            ->withHeader('Mcp-Session-Id', $sessionId)
    );
    expect($getResponse->getStatusCode())->toBe(200);
    expect($getResponse->getHeaderLine('Content-Type'))->toStartWith('text/event-stream');

    $stdioSurface = kirbyMcpStdioSurface();

    $httpTools = [];
    foreach (($toolsPayload['result']['tools'] ?? []) as $tool) {
        if (is_array($tool) && is_string($tool['name'] ?? null)) {
            $httpTools[$tool['name']] = true;
        }
    }

    $httpResources = [];
    foreach (($resourcesPayload['result']['resources'] ?? []) as $resource) {
        if (is_array($resource) && is_string($resource['uri'] ?? null)) {
            $httpResources[$resource['uri']] = true;
        }
    }

    foreach (['kirby_info', 'kirby_read_page_content', 'kirby_update_page_content'] as $toolName) {
        expect($stdioSurface['tools'])->toHaveKey($toolName);
        expect($httpTools)->toHaveKey($toolName);
    }

    foreach (['kirby://glossary', 'kirby://kb', 'kirby://fields/update-schema'] as $resourceUri) {
        expect($stdioSurface['resources'])->toHaveKey($resourceUri);
        expect($httpResources)->toHaveKey($resourceUri);
    }
});

it('rejects malformed MCP session ids before delegating non-GET requests', function (): void {
    $factory = new HttpFactory();
    $sessionDir = sys_get_temp_dir() . '/kirby-mcp-http-test-' . bin2hex(random_bytes(6));
    $sessionStore = new FileSessionStore($sessionDir);
    $tracer = new HttpMcpTracer(new ServerFactory(), $sessionStore);

    foreach (['POST', 'DELETE'] as $method) {
        $request = $factory->createServerRequest($method, 'http://127.0.0.1/mcp')
            ->withHeader('Mcp-Session-Id', 'not-a-uuid')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream(kirbyMcpHttpJsonRequest('tools/list', 2)));

        $response = $tracer->handle($request);

        expect($response->getStatusCode())->toBe(400);
        expect((string) $response->getBody())->toContain('Invalid Mcp-Session-Id header.');
    }
});
