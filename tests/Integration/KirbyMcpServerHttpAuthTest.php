<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Http\HttpAuthScopes;
use Bnomei\KirbyMcp\Mcp\Http\SharedTokenValidator;
use Bnomei\KirbyMcp\Mcp\HttpMcpHandler;
use Bnomei\KirbyMcp\Mcp\ServerFactory;
use GuzzleHttp\Psr7\HttpFactory;
use Mcp\Server\Session\FileSessionStore;
use Psr\Http\Message\ResponseInterface;

function kirbyMcpHttpAuthJsonRequest(string $method, int|string|null $id = null, array $params = []): string
{
    $payload = [
        'jsonrpc' => '2.0',
        'method' => $method,
        'params' => $params === [] ? new stdClass() : $params,
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
    }

    return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
}

function kirbyMcpHttpAuthDecode(ResponseInterface $response): array
{
    $decoded = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    expect($decoded)->toBeArray();

    return $decoded;
}

/**
 * @param list<string> $allowedOrigins
 */
function kirbyMcpHttpAuthHandler(?SharedTokenValidator $validator = null, array $allowedOrigins = []): HttpMcpHandler
{
    $sessionDir = sys_get_temp_dir() . '/kirby-mcp-http-auth-test-' . bin2hex(random_bytes(6));

    return new HttpMcpHandler(
        serverFactory: new ServerFactory(),
        sessionStore: new FileSessionStore($sessionDir),
        tokenValidator: $validator ?? new SharedTokenValidator('local-secret'),
        allowedOrigins: $allowedOrigins,
    );
}

it('rejects missing malformed invalid and query-string bearer credentials before MCP handling', function (): void {
    $factory = new HttpFactory();
    $handler = kirbyMcpHttpAuthHandler();
    $body = $factory->createStream(kirbyMcpHttpAuthJsonRequest('initialize', 1));

    $missing = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($body)
    );
    expect($missing->getStatusCode())->toBe(401)
        ->and($missing->getHeaderLine('WWW-Authenticate'))->toContain('Bearer')
        ->and((string) $missing->getBody())->toContain('authorization_required');

    $malformed = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Basic nope')
            ->withBody($factory->createStream(kirbyMcpHttpAuthJsonRequest('initialize', 1)))
    );
    expect($malformed->getStatusCode())->toBe(400)
        ->and((string) $malformed->getBody())->toContain('invalid_request');

    $invalid = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer wrong-secret')
            ->withBody($factory->createStream(kirbyMcpHttpAuthJsonRequest('initialize', 1)))
    );
    expect($invalid->getStatusCode())->toBe(401)
        ->and((string) $invalid->getBody())->toContain('invalid_token');

    $query = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp?access_token=local-secret')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer local-secret')
            ->withBody($factory->createStream(kirbyMcpHttpAuthJsonRequest('initialize', 1)))
    );
    expect($query->getStatusCode())->toBe(400)
        ->and((string) $query->getBody())->toContain('Query-string credentials are not allowed.');
});

it('rejects disallowed Origin before auth and protocol handling', function (): void {
    $factory = new HttpFactory();
    $handler = kirbyMcpHttpAuthHandler(allowedOrigins: ['http://allowed.example.test']);

    $response = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Origin', 'http://blocked.example.test')
            ->withBody($factory->createStream(kirbyMcpHttpAuthJsonRequest('initialize', 1)))
    );

    expect($response->getStatusCode())->toBe(403)
        ->and((string) $response->getBody())->toContain('Origin is not allowed.');
});

it('requires write scope for kirby_run_cli_command with allowWrite even on an execute token', function (): void {
    $factory = new HttpFactory();
    $handler = kirbyMcpHttpAuthHandler(new SharedTokenValidator('exec-token', [HttpAuthScopes::READ, HttpAuthScopes::EXECUTE]));

    $initialize = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer exec-token')
            ->withBody($factory->createStream(kirbyMcpHttpAuthJsonRequest('initialize', 1)))
    );
    $sessionId = $initialize->getHeaderLine('Mcp-Session-Id');

    $writeCli = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer exec-token')
            ->withHeader('Mcp-Session-Id', $sessionId)
            ->withBody($factory->createStream(kirbyMcpHttpAuthJsonRequest('tools/call', 2, [
                'name' => 'kirby_run_cli_command',
                'arguments' => ['command' => 'clear:cache', 'allowWrite' => true],
            ])))
    );

    expect($writeCli->getStatusCode())->toBe(403)
        ->and($writeCli->getHeaderLine('WWW-Authenticate'))->toContain('insufficient_scope')
        ->and((string) $writeCli->getBody())->toContain(HttpAuthScopes::WRITE);
});

it('keeps tools discoverable but rejects calls when the bearer token lacks operation scope', function (): void {
    $factory = new HttpFactory();
    $handler = kirbyMcpHttpAuthHandler(new SharedTokenValidator('read-token', [HttpAuthScopes::READ]));

    $initialize = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer read-token')
            ->withBody($factory->createStream(kirbyMcpHttpAuthJsonRequest('initialize', 1)))
    );
    expect($initialize->getStatusCode())->toBe(200);
    $sessionId = $initialize->getHeaderLine('Mcp-Session-Id');

    $tools = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer read-token')
            ->withHeader('Mcp-Session-Id', $sessionId)
            ->withBody($factory->createStream(kirbyMcpHttpAuthJsonRequest('tools/list', 2)))
    );
    expect($tools->getStatusCode())->toBe(200);
    $toolPayload = kirbyMcpHttpAuthDecode($tools);
    $toolNames = array_map(static fn (array $tool): string => (string) ($tool['name'] ?? ''), $toolPayload['result']['tools'] ?? []);
    expect($toolNames)->toContain('kirby_update_page_content')
        ->and($toolNames)->toContain('kirby_eval')
        ->and($toolNames)->toContain('kirby_cache_clear');

    $writeCall = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer read-token')
            ->withHeader('Mcp-Session-Id', $sessionId)
            ->withBody($factory->createStream(kirbyMcpHttpAuthJsonRequest('tools/call', 3, [
                'name' => 'kirby_update_page_content',
                'arguments' => new stdClass(),
            ])))
    );

    expect($writeCall->getStatusCode())->toBe(403)
        ->and($writeCall->getHeaderLine('WWW-Authenticate'))->toContain('insufficient_scope')
        ->and((string) $writeCall->getBody())->toContain(HttpAuthScopes::WRITE);

    $executeCall = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer read-token')
            ->withHeader('Mcp-Session-Id', $sessionId)
            ->withBody($factory->createStream(kirbyMcpHttpAuthJsonRequest('tools/call', 4, [
                'name' => 'kirby_eval',
                'arguments' => [
                    'code' => 'return 1;',
                    'confirm' => true,
                ],
            ])))
    );

    expect($executeCall->getStatusCode())->toBe(403)
        ->and((string) $executeCall->getBody())->toContain(HttpAuthScopes::EXECUTE);

    $adminCall = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer read-token')
            ->withHeader('Mcp-Session-Id', $sessionId)
            ->withBody($factory->createStream(kirbyMcpHttpAuthJsonRequest('tools/call', 5, [
                'name' => 'kirby_cache_clear',
                'arguments' => new stdClass(),
            ])))
    );

    expect($adminCall->getStatusCode())->toBe(403)
        ->and((string) $adminCall->getBody())->toContain(HttpAuthScopes::ADMIN);

    $runtimeToolCall = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer read-token')
            ->withHeader('Mcp-Session-Id', $sessionId)
            ->withBody($factory->createStream(kirbyMcpHttpAuthJsonRequest('tools/call', 6, [
                'name' => 'kirby_blueprint_read',
                'arguments' => ['id' => 'pages/home'],
            ])))
    );

    expect($runtimeToolCall->getStatusCode())->toBe(403)
        ->and($runtimeToolCall->getHeaderLine('WWW-Authenticate'))->toContain('insufficient_scope')
        ->and((string) $runtimeToolCall->getBody())->toContain(HttpAuthScopes::RUNTIME);

    $loggingCall = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer read-token')
            ->withHeader('Mcp-Session-Id', $sessionId)
            ->withBody($factory->createStream(kirbyMcpHttpAuthJsonRequest('logging/setLevel', 7, [
                'level' => 'debug',
            ])))
    );

    expect($loggingCall->getStatusCode())->toBe(403)
        ->and((string) $loggingCall->getBody())->toContain(HttpAuthScopes::ADMIN);

    $runtimeResourceRead = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer read-token')
            ->withHeader('Mcp-Session-Id', $sessionId)
            ->withBody($factory->createStream(kirbyMcpHttpAuthJsonRequest('resources/read', 8, [
                'uri' => 'kirby://page/content/home',
            ])))
    );

    expect($runtimeResourceRead->getStatusCode())->toBe(403)
        ->and($runtimeResourceRead->getHeaderLine('WWW-Authenticate'))->toContain('insufficient_scope')
        ->and((string) $runtimeResourceRead->getBody())->toContain(HttpAuthScopes::RUNTIME);

    $staticResourceRead = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer read-token')
            ->withHeader('Mcp-Session-Id', $sessionId)
            ->withBody($factory->createStream(kirbyMcpHttpAuthJsonRequest('resources/read', 9, [
                'uri' => 'kirby://kb',
            ])))
    );

    expect($staticResourceRead->getStatusCode())->toBe(200);
});

it('allows a valid scoped bearer token to initialize and call read operations', function (): void {
    $factory = new HttpFactory();
    $handler = kirbyMcpHttpAuthHandler(new SharedTokenValidator('read-token', [HttpAuthScopes::READ]));

    $initialize = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer read-token')
            ->withBody($factory->createStream(kirbyMcpHttpAuthJsonRequest('initialize', 1)))
    );
    expect($initialize->getStatusCode())->toBe(200);

    $resources = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer read-token')
            ->withHeader('Mcp-Session-Id', $initialize->getHeaderLine('Mcp-Session-Id'))
            ->withBody($factory->createStream(kirbyMcpHttpAuthJsonRequest('resources/list', 2)))
    );

    expect($resources->getStatusCode())->toBe(200);
    $payload = kirbyMcpHttpAuthDecode($resources);
    expect($payload['result']['resources'] ?? [])->not()->toBeEmpty();
});
