<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\HttpMcpHandler;
use Bnomei\KirbyMcp\Mcp\ServerFactory;
use GuzzleHttp\Psr7\HttpFactory;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Session\Session;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Uid\Uuid;

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

function kirbyMcpHttpAuthorize(mixed $request): mixed
{
    return $request->withHeader('Authorization', 'Bearer local-secret');
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
                expectCodexSafeResourceDescriptor($resource);
                $resources[$resource['uri']] = true;
            }
        }
    }

    return [
        'tools' => $tools,
        'resources' => $resources,
    ];
}

function kirbyMcpUnusedTcpPort(): int
{
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $candidate = random_int(20000, 45000);
        $probe = @stream_socket_server('tcp://127.0.0.1:' . $candidate);
        if ($probe === false) {
            continue;
        }

        fclose($probe);

        return $candidate;
    }

    return 0;
}

function kirbyMcpReadSocketUntil(mixed $client, string $needle, float $seconds): string
{
    $buffer = '';
    $deadline = microtime(true) + $seconds;
    stream_set_timeout($client, 1);

    while (microtime(true) < $deadline && !str_contains($buffer, $needle)) {
        $chunk = fread($client, 4096);
        if (is_string($chunk) && $chunk !== '') {
            $buffer .= $chunk;
        }

        $meta = stream_get_meta_data($client);
        if (($meta['eof'] ?? false) === true) {
            break;
        }
    }

    return $buffer;
}

it('serves the MCP server over a single /mcp HTTP endpoint with reusable session state', function (): void {
    $factory = new HttpFactory();
    $sessionDir = sys_get_temp_dir() . '/kirby-mcp-http-test-' . bin2hex(random_bytes(6));
    $sessionStore = new FileSessionStore($sessionDir);
    $handler = new HttpMcpHandler(new ServerFactory(), $sessionStore, sharedToken: 'local-secret');

    $initializeRequest = kirbyMcpHttpAuthorize($factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
        ->withHeader('Content-Type', 'application/json')
        ->withBody($factory->createStream(kirbyMcpHttpJsonRequest('initialize', 1))));

    $initializeResponse = $handler->handle($initializeRequest);
    expect($initializeResponse->getStatusCode())->toBe(200);
    expect($initializeResponse->getHeaderLine('Mcp-Session-Id'))->not()->toBe('');

    $sessionId = $initializeResponse->getHeaderLine('Mcp-Session-Id');
    $initializePayload = kirbyMcpHttpDecodeResponse($initializeResponse);
    expect($initializePayload['result']['serverInfo']['name'] ?? null)->toBe('Kirby MCP');

    $initializedRequest = kirbyMcpHttpAuthorize($factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('Mcp-Session-Id', $sessionId)
        ->withBody($factory->createStream(kirbyMcpHttpJsonRequest('notifications/initialized'))));
    expect($handler->handle($initializedRequest)->getStatusCode())->toBe(202);

    $toolsRequest = kirbyMcpHttpAuthorize($factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('Mcp-Session-Id', $sessionId)
        ->withBody($factory->createStream(kirbyMcpHttpJsonRequest('tools/list', 2))));
    $toolsResponse = $handler->handle($toolsRequest);
    expect($toolsResponse->getStatusCode())->toBe(200);
    expect($toolsResponse->getHeaderLine('Mcp-Session-Id'))->toBe($sessionId);
    $toolsPayload = kirbyMcpHttpDecodeResponse($toolsResponse);

    $resourcesRequest = kirbyMcpHttpAuthorize($factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('Mcp-Session-Id', $sessionId)
        ->withBody($factory->createStream(kirbyMcpHttpJsonRequest('resources/list', 3))));
    $resourcesResponse = $handler->handle($resourcesRequest);
    expect($resourcesResponse->getStatusCode())->toBe(200);
    expect($resourcesResponse->getHeaderLine('Mcp-Session-Id'))->toBe($sessionId);
    $resourcesPayload = kirbyMcpHttpDecodeResponse($resourcesResponse);

    $getResponse = $handler->handle(
        kirbyMcpHttpAuthorize($factory->createServerRequest('GET', 'http://127.0.0.1/mcp')
            ->withHeader('Mcp-Session-Id', $sessionId))
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
            expectCodexSafeResourceDescriptor($resource);
            $httpResources[$resource['uri']] = true;
            if ($resource['uri'] === 'kirby://kb') {
                expect($resource['title'] ?? null)->toBe('Kirby Knowledge Base');
            }
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

    $deleteResponse = $handler->handle(
        kirbyMcpHttpAuthorize($factory->createServerRequest('DELETE', 'http://127.0.0.1/mcp')
            ->withHeader('Mcp-Session-Id', $sessionId))
    );
    expect($deleteResponse->getStatusCode())->toBe(200);

    $afterDeleteResponse = $handler->handle(
        kirbyMcpHttpAuthorize($factory->createServerRequest('GET', 'http://127.0.0.1/mcp')
            ->withHeader('Mcp-Session-Id', $sessionId))
    );
    expect($afterDeleteResponse->getStatusCode())->toBe(404);
});

it('rejects malformed MCP session ids before delegating non-GET requests', function (): void {
    $factory = new HttpFactory();
    $sessionDir = sys_get_temp_dir() . '/kirby-mcp-http-test-' . bin2hex(random_bytes(6));
    $sessionStore = new FileSessionStore($sessionDir);
    $handler = new HttpMcpHandler(new ServerFactory(), $sessionStore, sharedToken: 'local-secret');

    foreach (['POST', 'DELETE'] as $method) {
        $request = kirbyMcpHttpAuthorize($factory->createServerRequest($method, 'http://127.0.0.1/mcp')
            ->withHeader('Mcp-Session-Id', 'not-a-uuid')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream(kirbyMcpHttpJsonRequest('tools/list', 2))));

        $response = $handler->handle($request);

        expect($response->getStatusCode())->toBe(400);
        expect((string) $response->getBody())->toContain('Invalid Mcp-Session-Id header.');
    }
});

it('enforces Streamable HTTP session header semantics for missing and unknown sessions', function (): void {
    $factory = new HttpFactory();
    $sessionDir = sys_get_temp_dir() . '/kirby-mcp-http-test-' . bin2hex(random_bytes(6));
    $sessionStore = new FileSessionStore($sessionDir);
    $handler = new HttpMcpHandler(new ServerFactory(), $sessionStore, sharedToken: 'local-secret');
    $unknownSessionId = '018f64a5-4690-7420-8c49-91609fdb78a8';

    $missingPostResponse = $handler->handle(
        kirbyMcpHttpAuthorize(
            $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream(kirbyMcpHttpJsonRequest('tools/list', 2)))
        )
    );
    expect($missingPostResponse->getStatusCode())->toBe(400);

    $unknownPostResponse = $handler->handle(
        kirbyMcpHttpAuthorize(
            $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Mcp-Session-Id', $unknownSessionId)
            ->withBody($factory->createStream(kirbyMcpHttpJsonRequest('tools/list', 2)))
        )
    );
    expect($unknownPostResponse->getStatusCode())->toBe(404);

    $missingGetResponse = $handler->handle(
        kirbyMcpHttpAuthorize($factory->createServerRequest('GET', 'http://127.0.0.1/mcp'))
    );
    expect($missingGetResponse->getStatusCode())->toBe(400);

    $unknownGetResponse = $handler->handle(
        kirbyMcpHttpAuthorize($factory->createServerRequest('GET', 'http://127.0.0.1/mcp')
            ->withHeader('Mcp-Session-Id', $unknownSessionId))
    );
    expect($unknownGetResponse->getStatusCode())->toBe(404);

    $missingDeleteResponse = $handler->handle(
        kirbyMcpHttpAuthorize($factory->createServerRequest('DELETE', 'http://127.0.0.1/mcp'))
    );
    expect($missingDeleteResponse->getStatusCode())->toBe(400);

    $unknownDeleteResponse = $handler->handle(
        kirbyMcpHttpAuthorize($factory->createServerRequest('DELETE', 'http://127.0.0.1/mcp')
            ->withHeader('Mcp-Session-Id', $unknownSessionId))
    );
    expect($unknownDeleteResponse->getStatusCode())->toBe(404);
});

it('answers Streamable HTTP CORS preflight on the MCP endpoint', function (): void {
    $factory = new HttpFactory();
    $sessionDir = sys_get_temp_dir() . '/kirby-mcp-http-test-' . bin2hex(random_bytes(6));
    $sessionStore = new FileSessionStore($sessionDir);
    $handler = new HttpMcpHandler(new ServerFactory(), $sessionStore, sharedToken: 'local-secret');

    $missingAuthResponse = $handler->handle(
        $factory->createServerRequest('OPTIONS', 'http://127.0.0.1/mcp')
    );
    expect($missingAuthResponse->getStatusCode())->toBe(401);

    $queryCredentialResponse = $handler->handle(
        $factory->createServerRequest('OPTIONS', 'http://127.0.0.1/mcp?access_token=local-secret')
    );
    expect($queryCredentialResponse->getStatusCode())->toBe(400);
    expect((string) $queryCredentialResponse->getBody())->toContain('Query-string credentials are not allowed.');

    $response = $handler->handle(
        $factory->createServerRequest('OPTIONS', 'http://127.0.0.1/mcp')
            ->withHeader('Authorization', 'Bearer local-secret')
            ->withHeader('Origin', 'http://localhost:3000')
            ->withHeader('Access-Control-Request-Method', 'POST')
    );

    expect($response->getStatusCode())->toBe(204);
    expect($response->getHeaderLine('Access-Control-Allow-Methods'))->toContain('GET');
    expect($response->getHeaderLine('Access-Control-Allow-Headers'))->toContain('Mcp-Session-Id');
});

it('enforces shared-token authorization and origin policy before protocol handling', function (): void {
    $factory = new HttpFactory();
    $sessionDir = sys_get_temp_dir() . '/kirby-mcp-http-test-' . bin2hex(random_bytes(6));
    $sessionStore = new FileSessionStore($sessionDir);
    $handler = new HttpMcpHandler(
        serverFactory: new ServerFactory(),
        sessionStore: $sessionStore,
        sharedToken: 'local-secret',
        allowedOrigins: ['http://allowed.example.test'],
    );

    $missingAuthResponse = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream(kirbyMcpHttpJsonRequest('initialize', 1)))
    );
    expect($missingAuthResponse->getStatusCode())->toBe(401);

    $badAuthResponse = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer wrong-secret')
            ->withBody($factory->createStream(kirbyMcpHttpJsonRequest('initialize', 1)))
    );
    expect($badAuthResponse->getStatusCode())->toBe(401);

    $badOriginResponse = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer local-secret')
            ->withHeader('Origin', 'http://blocked.example.test')
            ->withBody($factory->createStream(kirbyMcpHttpJsonRequest('initialize', 1)))
    );
    expect($badOriginResponse->getStatusCode())->toBe(403);

    $allowedResponse = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer local-secret')
            ->withHeader('Origin', 'http://allowed.example.test')
            ->withBody($factory->createStream(kirbyMcpHttpJsonRequest('initialize', 1)))
    );
    expect($allowedResponse->getStatusCode())->toBe(200);
    expect($allowedResponse->getHeaderLine('Mcp-Session-Id'))->not()->toBe('');

    $badProtocolResponse = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer local-secret')
            ->withHeader('Origin', 'http://allowed.example.test')
            ->withHeader('Mcp-Session-Id', $allowedResponse->getHeaderLine('Mcp-Session-Id'))
            ->withHeader('Mcp-Protocol-Version', '1900-01-01')
            ->withBody($factory->createStream(kirbyMcpHttpJsonRequest('tools/list', 2)))
    );
    expect($badProtocolResponse->getStatusCode())->toBe(400);
    expect((string) $badProtocolResponse->getBody())->toContain('Unsupported Mcp-Protocol-Version header value');

    $badGetProtocolResponse = $handler->handle(
        $factory->createServerRequest('GET', 'http://127.0.0.1/mcp')
            ->withHeader('Authorization', 'Bearer local-secret')
            ->withHeader('Origin', 'http://allowed.example.test')
            ->withHeader('Mcp-Session-Id', $allowedResponse->getHeaderLine('Mcp-Session-Id'))
            ->withHeader('Mcp-Protocol-Version', '1900-01-01')
    );
    expect($badGetProtocolResponse->getStatusCode())->toBe(400);
    expect((string) $badGetProtocolResponse->getBody())->toContain('Unsupported Mcp-Protocol-Version header value');

    $getResponse = $handler->handle(
        $factory->createServerRequest('GET', 'http://127.0.0.1/mcp')
            ->withHeader('Authorization', 'Bearer local-secret')
            ->withHeader('Origin', 'http://allowed.example.test')
            ->withHeader('Mcp-Session-Id', $allowedResponse->getHeaderLine('Mcp-Session-Id'))
    );
    expect($getResponse->getStatusCode())->toBe(200);
    expect($getResponse->getHeaderLine('Content-Type'))->toStartWith('text/event-stream');
    expect($getResponse->getHeaderLine('Access-Control-Allow-Origin'))->toBe('http://allowed.example.test');
    expect($getResponse->getHeaderLine('Access-Control-Expose-Headers'))->toContain('Mcp-Session-Id');
});

it('starts the opt-in kirby-mcp http listener and serves /mcp', function (): void {
    $bin = realpath(__DIR__ . '/../../bin/kirby-mcp');
    expect($bin)->not()->toBeFalse();

    $port = kirbyMcpUnusedTcpPort();
    if ($port === 0) {
        $this->markTestSkipped('Local TCP listeners are unavailable in this environment.');
    }

    expect($port)->toBeGreaterThan(0);

    $process = new Process(
        command: [PHP_BINARY, $bin, 'http', '--project=' . cmsPath(), '--once'],
        cwd: dirname(__DIR__, 2),
        env: [
            'KIRBY_MCP_HTTP_ENABLED' => '1',
            'KIRBY_MCP_HTTP_PORT' => (string) $port,
            'KIRBY_MCP_HTTP_AUTH_MODE' => 'shared-token',
            'KIRBY_MCP_HTTP_TOKEN' => 'local-secret',
        ],
        timeout: 10,
    );
    $process->start();

    $listening = false;
    $deadline = microtime(true) + 5.0;
    while (microtime(true) < $deadline) {
        if (str_contains($process->getIncrementalErrorOutput(), 'Kirby MCP HTTP listening')) {
            $listening = true;
            break;
        }
        usleep(100000);
    }

    expect($listening)->toBeTrue($process->getErrorOutput());

    $client = stream_socket_client('tcp://127.0.0.1:' . $port, $errno, $errstr, 1.0);
    if (!is_resource($client)) {
        throw new RuntimeException('Unable to connect to the local kirby-mcp http listener.');
    }

    fwrite($client, "OPTIONS /mcp HTTP/1.1\r\nHost: 127.0.0.1:{$port}\r\nAuthorization: Bearer local-secret\r\nConnection: close\r\n\r\n");
    $raw = stream_get_contents($client);
    fclose($client);

    $process->wait();

    expect($process->getExitCode())->toBe(0);
    expect($raw)->toBeString();
    expect((string) $raw)->toContain('HTTP/1.1 204');
    expect((string) $raw)->toContain('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
});

it('serves later queued GET SSE messages through the opt-in listener', function (): void {
    if (!function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl_fork is required for listener-level SSE concurrency.');
    }

    $bin = realpath(__DIR__ . '/../../bin/kirby-mcp');
    expect($bin)->not()->toBeFalse();

    $port = kirbyMcpUnusedTcpPort();
    if ($port === 0) {
        $this->markTestSkipped('Local TCP listeners are unavailable in this environment.');
    }

    $sessionDir = cmsPath() . DIRECTORY_SEPARATOR . '.kirby-mcp' . DIRECTORY_SEPARATOR . 'http-sessions';
    $sessionStore = new FileSessionStore($sessionDir);
    $sessionId = Uuid::v4();
    expect($sessionStore->write($sessionId, '{}'))->toBeTrue();

    $process = new Process(
        command: [PHP_BINARY, $bin, 'http', '--project=' . cmsPath()],
        cwd: dirname(__DIR__, 2),
        env: [
            'KIRBY_MCP_HTTP_ENABLED' => '1',
            'KIRBY_MCP_HTTP_PORT' => (string) $port,
            'KIRBY_MCP_HTTP_AUTH_MODE' => 'shared-token',
            'KIRBY_MCP_HTTP_TOKEN' => 'local-secret',
            'KIRBY_MCP_HTTP_SSE_MAX_SECONDS' => '3',
        ],
        timeout: 10,
    );
    $process->start();
    $client = null;

    try {
        $listening = false;
        $deadline = microtime(true) + 5.0;
        while (microtime(true) < $deadline) {
            if (str_contains($process->getIncrementalErrorOutput(), 'Kirby MCP HTTP listening')) {
                $listening = true;
                break;
            }
            usleep(100000);
        }

        expect($listening)->toBeTrue($process->getErrorOutput());

        $client = stream_socket_client('tcp://127.0.0.1:' . $port, $errno, $errstr, 1.0);
        if (!is_resource($client)) {
            throw new RuntimeException('Unable to connect to the local kirby-mcp http listener.');
        }

        fwrite(
            $client,
            "GET /mcp HTTP/1.1\r\n"
            . "Host: 127.0.0.1:{$port}\r\n"
            . "Authorization: Bearer local-secret\r\n"
            . "Mcp-Session-Id: {$sessionId->toRfc4122()}\r\n"
            . "Connection: keep-alive\r\n\r\n"
        );

        $raw = kirbyMcpReadSocketUntil($client, ': connected', 3.0);

        $session = new Session($sessionStore, $sessionId);
        $session->set('_mcp.outgoing_queue', [[
            'message' => '{"jsonrpc":"2.0","method":"notifications/message","params":{"level":"info","data":"queued after get"}}',
            'context' => ['type' => 'notification'],
        ]]);
        $session->save();

        $raw .= kirbyMcpReadSocketUntil($client, 'queued after get', 4.0);
        fclose($client);
        $process->stop(1);

        expect($raw)->toBeString();
        expect((string) $raw)->toContain('HTTP/1.1 200');
        expect((string) $raw)->toContain('Content-Type: text/event-stream');
        expect((string) $raw)->toContain('Connection: keep-alive');
        expect((string) $raw)->not()->toContain('Connection: close');
        expect((string) $raw)->toContain(': connected');
        expect((string) $raw)->toContain('event: message');
        expect((string) $raw)->toContain('queued after get');
    } finally {
        if (is_resource($client)) {
            fclose($client);
        }

        if ($process->isRunning()) {
            $process->stop(1);
        }
    }
});
