<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\KirbyMcpRoute;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use GuzzleHttp\Psr7\HttpFactory;

/**
 * @return array<string, mixed>
 */
function kirbyMcpRouteDecodeJson(string $body): array
{
    $decoded = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
    expect($decoded)->toBeArray();

    return $decoded;
}

function kirbyMcpRouteInitializePayload(): string
{
    return json_encode([
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
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
}

/**
 * @param array<string, mixed> $params
 */
function kirbyMcpRouteJsonRpcPayload(string $method, int $id, array $params = []): string
{
    return json_encode([
        'jsonrpc' => '2.0',
        'id' => $id,
        'method' => $method,
        'params' => $params === [] ? new stdClass() : $params,
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
}

function kirbyMcpRouteNotificationPayload(string $method): string
{
    return json_encode([
        'jsonrpc' => '2.0',
        'method' => $method,
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
}

/**
 * @param array<string, string> $env
 */
function kirbyMcpRouteWithHttpEnv(array $env, Closure $callback): mixed
{
    $names = [
        'KIRBY_MCP_HTTP_ENABLED',
        'KIRBY_MCP_HTTP_HOST',
        'KIRBY_MCP_HTTP_PORT',
        'KIRBY_MCP_HTTP_PATH',
        'KIRBY_MCP_HTTP_ALLOWED_ORIGINS',
        'KIRBY_MCP_HTTP_AUTH_MODE',
        'KIRBY_MCP_HTTP_TOKEN',
        'KIRBY_MCP_HTTP_OAUTH_ISSUER',
        'KIRBY_MCP_HTTP_OAUTH_AUDIENCE',
        'KIRBY_MCP_HTTP_OAUTH_JWKS_URI',
        'KIRBY_MCP_HTTP_SCOPES',
    ];

    $previous = [];
    foreach ($names as $name) {
        $value = getenv($name);
        $previous[$name] = is_string($value) ? $value : false;
        putenv($name);
    }

    foreach ($env as $name => $value) {
        putenv($name . '=' . $value);
    }

    KirbyMcpConfig::clearCache();

    try {
        return $callback();
    } finally {
        foreach ($previous as $name => $value) {
            if ($value === false) {
                putenv($name);
                continue;
            }

            putenv($name . '=' . $value);
        }

        KirbyMcpConfig::clearCache();
    }
}

it('keeps the copied Kirby MCP route closed while HTTP is disabled', function (): void {
    kirbyMcpRouteWithHttpEnv([], function (): void {
        $factory = new HttpFactory();
        $request = $factory->createServerRequest('POST', 'http://127.0.0.1/mcp');

        $response = KirbyMcpRoute::handle(cmsPath(), $request);

        expect($response->code())->toBe(404);
        expect($response->body())->toContain('HTTP MCP route is disabled.');
    });
});

it('serves initialize through the opt-in Kirby route adapter', function (): void {
    kirbyMcpRouteWithHttpEnv([
        'KIRBY_MCP_HTTP_ENABLED' => '1',
        'KIRBY_MCP_HTTP_AUTH_MODE' => 'shared-token',
        'KIRBY_MCP_HTTP_TOKEN' => 'local-secret',
    ], function (): void {
        $factory = new HttpFactory();
        $request = $factory->createServerRequest('POST', 'http://127.0.0.1/mcp', [
            'REMOTE_ADDR' => '127.0.0.1',
        ])
            ->withHeader('Authorization', 'Bearer local-secret')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream(kirbyMcpRouteInitializePayload()));

        $response = KirbyMcpRoute::handle(cmsPath(), $request);

        expect($response->code())->toBe(200);
        expect($response->headers()['Mcp-Session-Id'] ?? '')->not()->toBe('');

        $payload = kirbyMcpRouteDecodeJson($response->body());
        expect($payload['result']['serverInfo']['name'] ?? null)->toBe('Kirby MCP');
    });
});

it('fails closed for shared-token Kirby route requests on public hosts', function (): void {
    kirbyMcpRouteWithHttpEnv([
        'KIRBY_MCP_HTTP_ENABLED' => '1',
        'KIRBY_MCP_HTTP_AUTH_MODE' => 'shared-token',
        'KIRBY_MCP_HTTP_TOKEN' => 'local-secret',
    ], function (): void {
        $factory = new HttpFactory();
        $request = $factory->createServerRequest('POST', 'https://example.test/mcp', [
            'REMOTE_ADDR' => '203.0.113.10',
        ])
            ->withHeader('Authorization', 'Bearer local-secret')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream(kirbyMcpRouteInitializePayload()));

        $response = KirbyMcpRoute::handle(cmsPath(), $request);

        expect($response->code())->toBe(503);
        expect($response->body())->toContain('HTTP shared-token auth is only allowed for loopback requests.');
    });
});

it('does not trust a spoofed Host header for shared-token Kirby routes', function (): void {
    kirbyMcpRouteWithHttpEnv([
        'KIRBY_MCP_HTTP_ENABLED' => '1',
        'KIRBY_MCP_HTTP_AUTH_MODE' => 'shared-token',
        'KIRBY_MCP_HTTP_TOKEN' => 'local-secret',
    ], function (): void {
        $factory = new HttpFactory();
        $request = $factory->createServerRequest('POST', 'http://127.0.0.1/mcp', [
            'REMOTE_ADDR' => '203.0.113.10',
        ])
            ->withHeader('Host', '127.0.0.1')
            ->withHeader('Authorization', 'Bearer local-secret')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream(kirbyMcpRouteInitializePayload()));

        $response = KirbyMcpRoute::handle(cmsPath(), $request);

        expect($response->code())->toBe(503);
        expect($response->body())->toContain('HTTP shared-token auth is only allowed for loopback requests.');
    });
});

it('ignores low-level listener host and port validation for Kirby route requests', function (): void {
    kirbyMcpRouteWithHttpEnv([
        'KIRBY_MCP_HTTP_ENABLED' => '1',
        'KIRBY_MCP_HTTP_HOST' => '0.0.0.0',
        'KIRBY_MCP_HTTP_PORT' => '99999',
        'KIRBY_MCP_HTTP_AUTH_MODE' => 'shared-token',
        'KIRBY_MCP_HTTP_TOKEN' => 'local-secret',
    ], function (): void {
        $factory = new HttpFactory();
        $request = $factory->createServerRequest('POST', 'http://127.0.0.1/mcp', [
            'REMOTE_ADDR' => '127.0.0.1',
        ])
            ->withHeader('Authorization', 'Bearer local-secret')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream(kirbyMcpRouteInitializePayload()));

        $response = KirbyMcpRoute::handle(cmsPath(), $request);

        expect($response->code())->toBe(200);
        expect($response->headers()['Mcp-Session-Id'] ?? '')->not()->toBe('');
    });
});

it('propagates the resolved project root into the MCP tool context', function (): void {
    $previous = getenv('KIRBY_MCP_PROJECT_ROOT');
    putenv('KIRBY_MCP_PROJECT_ROOT');

    try {
        kirbyMcpRouteWithHttpEnv([
            'KIRBY_MCP_HTTP_ENABLED' => '1',
            'KIRBY_MCP_HTTP_AUTH_MODE' => 'shared-token',
            'KIRBY_MCP_HTTP_TOKEN' => 'local-secret',
        ], function (): void {
            $factory = new HttpFactory();
            $request = $factory->createServerRequest('POST', 'http://127.0.0.1/mcp', [
                'REMOTE_ADDR' => '127.0.0.1',
            ])
                ->withHeader('Authorization', 'Bearer local-secret')
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(kirbyMcpRouteInitializePayload()));

            $initializeResponse = KirbyMcpRoute::handle(cmsPath(), $request);
            expect($initializeResponse->code())->toBe(200);
            $sessionId = $initializeResponse->headers()['Mcp-Session-Id'] ?? '';
            expect($sessionId)->not()->toBe('');

            $initializedRequest = $factory->createServerRequest('POST', 'http://127.0.0.1/mcp', [
                'REMOTE_ADDR' => '127.0.0.1',
            ])
                ->withHeader('Authorization', 'Bearer local-secret')
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Mcp-Session-Id', $sessionId)
                ->withBody($factory->createStream(kirbyMcpRouteNotificationPayload('notifications/initialized')));
            expect(KirbyMcpRoute::handle(cmsPath(), $initializedRequest)->code())->toBe(202);

            $initToolRequest = $factory->createServerRequest('POST', 'http://127.0.0.1/mcp', [
                'REMOTE_ADDR' => '127.0.0.1',
            ])
                ->withHeader('Authorization', 'Bearer local-secret')
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Mcp-Session-Id', $sessionId)
                ->withBody($factory->createStream(kirbyMcpRouteJsonRpcPayload('tools/call', 2, [
                    'name' => 'kirby_init',
                    'arguments' => new stdClass(),
                ])));

            $initToolResponse = KirbyMcpRoute::handle(cmsPath(), $initToolRequest);
            expect($initToolResponse->code())->toBe(200);
            $payload = kirbyMcpRouteDecodeJson($initToolResponse->body());
            $text = $payload['result']['content'][0]['text'] ?? '';
            expect($text)->toBeString();
            expect($text)->toContain('`' . cmsPath() . '`');
        });
    } finally {
        if (is_string($previous)) {
            putenv('KIRBY_MCP_PROJECT_ROOT=' . $previous);
        } else {
            putenv('KIRBY_MCP_PROJECT_ROOT');
        }
    }
});
