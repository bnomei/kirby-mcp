<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\KirbyMcpRoute;
use Bnomei\KirbyMcp\Mcp\KirbyMcpOAuthRoute;
use Bnomei\KirbyMcp\Mcp\OAuth\OAuthKeySet;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Bnomei\KirbyMcp\Project\KirbyMcpHttpToken;
use GuzzleHttp\Psr7\HttpFactory;
use Kirby\Cms\App;

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
        'KIRBY_MCP_HTTP_REMOTE_TOKEN',
        'KIRBY_MCP_HTTP_REMOTE_TOKEN_HASH',
        'KIRBY_MCP_HTTP_REMOTE_TOKEN_ID',
        'KIRBY_MCP_HTTP_REMOTE_TOKEN_SCOPES',
        'KIRBY_MCP_HTTP_OAUTH_ISSUER',
        'KIRBY_MCP_HTTP_OAUTH_AUDIENCE',
        'KIRBY_MCP_HTTP_OAUTH_JWKS_URI',
        'KIRBY_MCP_HTTP_OAUTH_PROVIDER_ENABLED',
        'KIRBY_MCP_HTTP_OAUTH_PROVIDER_PATH',
        'KIRBY_MCP_HTTP_OAUTH_PROVIDER_CONSENT',
        'KIRBY_MCP_HTTP_OAUTH_PROVIDER_CONSENT_SNIPPET',
        'KIRBY_MCP_HTTP_SCOPES',
        'KIRBY_MCP_PROJECT_ROOT',
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

function kirbyMcpRouteRemoveDirectory(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $items = array_diff(scandir($path) ?: [], ['.', '..']);
    foreach ($items as $item) {
        $itemPath = $path . DIRECTORY_SEPARATOR . $item;
        if (is_dir($itemPath)) {
            kirbyMcpRouteRemoveDirectory($itemPath);
            continue;
        }

        @unlink($itemPath);
    }

    @rmdir($path);
}

function kirbyMcpRouteLocation(Kirby\Http\Response $response): string
{
    $location = $response->headers()['Location'] ?? '';
    $location = is_array($location) ? ($location[0] ?? '') : $location;

    return (string) $location;
}

function kirbyMcpRouteCommitSession(App $app): void
{
    try {
        $app->session()->commit();
    } catch (Throwable) {
        // Some route tests never start Kirby's session component.
    }
}

/**
 * @param array<string, mixed> $overrides
 * @return array<string, mixed>
 */
function kirbyMcpRouteRegisterOAuthClient(HttpFactory $factory, string $projectRoot, array $overrides = []): array
{
    $request = $factory->createServerRequest('POST', 'https://example.test/mcp/oauth/register', [
        'REMOTE_ADDR' => '203.0.113.10',
    ])
        ->withHeader('Content-Type', 'application/json')
        ->withBody($factory->createStream(json_encode([
            'client_name' => 'Claude Desktop',
            'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback'],
            'token_endpoint_auth_method' => 'none',
            ...$overrides,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)));

    $response = KirbyMcpOAuthRoute::handle($projectRoot, $request);
    expect($response->code())->toBe(201);

    return kirbyMcpRouteDecodeJson($response->body());
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

it('rejects shared-token public requests forwarded from local reverse proxies', function (): void {
    kirbyMcpRouteWithHttpEnv([
        'KIRBY_MCP_HTTP_ENABLED' => '1',
        'KIRBY_MCP_HTTP_AUTH_MODE' => 'shared-token',
        'KIRBY_MCP_HTTP_TOKEN' => 'local-secret',
    ], function (): void {
        $factory = new HttpFactory();
        $request = $factory->createServerRequest('POST', 'https://example.test/mcp', [
            'REMOTE_ADDR' => '127.0.0.1',
        ])
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

it('serves remote-token Kirby route requests from public HTTPS clients', function (): void {
    kirbyMcpRouteWithHttpEnv([
        'KIRBY_MCP_HTTP_ENABLED' => '1',
        'KIRBY_MCP_HTTP_AUTH_MODE' => 'remote-token',
        'KIRBY_MCP_HTTP_REMOTE_TOKEN' => 'remote-secret',
        'KIRBY_MCP_HTTP_REMOTE_TOKEN_ID' => 'claude-code',
    ], function (): void {
        $factory = new HttpFactory();
        $request = $factory->createServerRequest('POST', 'https://example.test/mcp', [
            'REMOTE_ADDR' => '203.0.113.10',
        ])
            ->withHeader('Authorization', 'Bearer remote-secret')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream(kirbyMcpRouteInitializePayload()));

        $response = KirbyMcpRoute::handle(cmsPath(), $request);

        expect($response->code())->toBe(200);
        expect($response->headers()['Mcp-Session-Id'] ?? '')->not()->toBe('');
    });
});

it('rejects remote-token Kirby route requests from public HTTP clients', function (): void {
    kirbyMcpRouteWithHttpEnv([
        'KIRBY_MCP_HTTP_ENABLED' => '1',
        'KIRBY_MCP_HTTP_AUTH_MODE' => 'remote-token',
        'KIRBY_MCP_HTTP_REMOTE_TOKEN_HASH' => KirbyMcpHttpToken::hashPlainText('remote-secret'),
    ], function (): void {
        $factory = new HttpFactory();
        $request = $factory->createServerRequest('POST', 'http://example.test/mcp', [
            'REMOTE_ADDR' => '203.0.113.10',
        ])
            ->withHeader('Authorization', 'Bearer remote-secret')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream(kirbyMcpRouteInitializePayload()));

        $response = KirbyMcpRoute::handle(cmsPath(), $request);

        expect($response->code())->toBe(503);
        expect($response->body())->toContain('HTTP remote-token auth requires HTTPS for non-loopback requests.');
    });
});

it('rejects remote-token public HTTP requests forwarded from local reverse proxies', function (): void {
    kirbyMcpRouteWithHttpEnv([
        'KIRBY_MCP_HTTP_ENABLED' => '1',
        'KIRBY_MCP_HTTP_AUTH_MODE' => 'remote-token',
        'KIRBY_MCP_HTTP_REMOTE_TOKEN' => 'remote-secret',
    ], function (): void {
        $factory = new HttpFactory();
        $request = $factory->createServerRequest('POST', 'http://example.test/mcp', [
            'REMOTE_ADDR' => '127.0.0.1',
        ])
            ->withHeader('Authorization', 'Bearer remote-secret')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream(kirbyMcpRouteInitializePayload()));

        $response = KirbyMcpRoute::handle(cmsPath(), $request);

        expect($response->code())->toBe(503);
        expect($response->body())->toContain('HTTP remote-token auth requires HTTPS for non-loopback requests.');
    });
});

it('allows remote-token HTTP only when the request is fully loopback', function (): void {
    kirbyMcpRouteWithHttpEnv([
        'KIRBY_MCP_HTTP_ENABLED' => '1',
        'KIRBY_MCP_HTTP_AUTH_MODE' => 'remote-token',
        'KIRBY_MCP_HTTP_REMOTE_TOKEN' => 'remote-secret',
    ], function (): void {
        $factory = new HttpFactory();
        $request = $factory->createServerRequest('POST', 'http://127.0.0.1/mcp', [
            'REMOTE_ADDR' => '127.0.0.1',
        ])
            ->withHeader('Authorization', 'Bearer remote-secret')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream(kirbyMcpRouteInitializePayload()));

        $response = KirbyMcpRoute::handle(cmsPath(), $request);

        expect($response->code())->toBe(200);
        expect($response->headers()['Mcp-Session-Id'] ?? '')->not()->toBe('');
    });
});

it('serves OAuth protected resource metadata through the Kirby route adapter', function (): void {
    kirbyMcpRouteWithHttpEnv([
        'KIRBY_MCP_HTTP_ENABLED' => '1',
        'KIRBY_MCP_HTTP_AUTH_MODE' => 'oauth',
        'KIRBY_MCP_HTTP_OAUTH_ISSUER' => 'https://auth.example.test',
        'KIRBY_MCP_HTTP_OAUTH_AUDIENCE' => 'https://example.test/mcp',
        'KIRBY_MCP_HTTP_OAUTH_JWKS_URI' => 'https://auth.example.test/.well-known/jwks.json',
    ], function (): void {
        $factory = new HttpFactory();
        $request = $factory->createServerRequest('GET', 'https://example.test/.well-known/oauth-protected-resource', [
            'REMOTE_ADDR' => '203.0.113.10',
        ]);

        $response = KirbyMcpRoute::handle(cmsPath(), $request);
        $payload = kirbyMcpRouteDecodeJson($response->body());

        expect($response->code())->toBe(200)
            ->and($payload['authorization_servers'] ?? null)->toBe(['https://auth.example.test'])
            ->and($payload['resource'] ?? null)->toBe('https://example.test/mcp')
            ->and($payload['scopes_supported'] ?? [])->toContain('kirby-mcp:read');
    });
});

it('serves the built-in OAuth provider flow for Claude Desktop custom connectors', function (): void {
    $projectRoot = cmsPath();
    $oauthStorage = $projectRoot . DIRECTORY_SEPARATOR . '.kirby-mcp' . DIRECTORY_SEPARATOR . 'oauth';
    kirbyMcpRouteRemoveDirectory($oauthStorage);

    $previousApp = App::instance(null, true);
    $previousErrorHandlers = captureErrorHandlers();
    $previousWhoops = App::$enableWhoops;
    App::$enableWhoops = false;
    $app = new App([
        'roots' => [
            'index' => $projectRoot,
        ],
    ]);
    ensureUser($app, 'mcp-oauth@example.com');
    $app->impersonate('mcp-oauth@example.com');

    try {
        kirbyMcpRouteWithHttpEnv([
            'KIRBY_MCP_HTTP_ENABLED' => '1',
            'KIRBY_MCP_HTTP_AUTH_MODE' => 'oauth',
            'KIRBY_MCP_HTTP_OAUTH_PROVIDER_ENABLED' => '1',
            'KIRBY_MCP_HTTP_SCOPES' => 'kirby-mcp:read,kirby-mcp:runtime',
        ], function () use ($projectRoot): void {
            $factory = new HttpFactory();

            $metadataRequest = $factory->createServerRequest('GET', 'https://example.test/.well-known/oauth-authorization-server', [
                'REMOTE_ADDR' => '203.0.113.10',
            ]);
            $metadataResponse = KirbyMcpOAuthRoute::handle($projectRoot, $metadataRequest);
            $metadata = kirbyMcpRouteDecodeJson($metadataResponse->body());
            expect($metadataResponse->code())->toBe(200)
                ->and($metadata['issuer'] ?? null)->toBe('https://example.test')
                ->and($metadata['authorization_endpoint'] ?? null)->toBe('https://example.test/mcp/oauth/authorize')
                ->and($metadata['token_endpoint'] ?? null)->toBe('https://example.test/mcp/oauth/token')
                ->and($metadata['registration_endpoint'] ?? null)->toBe('https://example.test/mcp/oauth/register')
                ->and($metadata['jwks_uri'] ?? null)->toBe('https://example.test/mcp/oauth/jwks.json')
                ->and($metadata['code_challenge_methods_supported'] ?? [])->toContain('S256');

            $registerRequest = $factory->createServerRequest('POST', 'https://example.test/mcp/oauth/register', [
                'REMOTE_ADDR' => '203.0.113.10',
            ])
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(json_encode([
                    'client_name' => 'Claude Desktop',
                    'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback'],
                    'token_endpoint_auth_method' => 'none',
                    'scope' => 'kirby-mcp:read kirby-mcp:runtime',
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)));
            $registerResponse = KirbyMcpOAuthRoute::handle($projectRoot, $registerRequest);
            $client = kirbyMcpRouteDecodeJson($registerResponse->body());
            expect($registerResponse->code())->toBe(201)
                ->and($client['client_id'] ?? null)->toBeString()
                ->and($client)->not()->toHaveKey('client_secret');

            $verifier = str_repeat('a', 43);
            $challenge = OAuthKeySet::base64Url(hash('sha256', $verifier, true));
            $authorizeQuery = http_build_query([
                'response_type' => 'code',
                'client_id' => $client['client_id'],
                'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
                'scope' => 'kirby-mcp:read kirby-mcp:runtime',
                'state' => 'state-123',
                'resource' => 'https://example.test/mcp',
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ], '', '&', PHP_QUERY_RFC3986);
            $authorizeRequest = $factory->createServerRequest('GET', 'https://example.test/mcp/oauth/authorize?' . $authorizeQuery, [
                'REMOTE_ADDR' => '203.0.113.10',
            ]);
            $authorizeResponse = KirbyMcpOAuthRoute::handle($projectRoot, $authorizeRequest);
            expect($authorizeResponse->code())->toBe(200);
            expect($authorizeResponse->body())
                ->toContain('Authorize Claude Desktop')
                ->toContain('kirby-mcp:read, kirby-mcp:runtime');
            expect(preg_match('/name="csrf" value="([^"]*)"/', $authorizeResponse->body(), $matches))->toBe(1);

            $approveRequest = $factory->createServerRequest('POST', 'https://example.test/mcp/oauth/authorize?' . $authorizeQuery, [
                'REMOTE_ADDR' => '203.0.113.10',
            ])
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody($factory->createStream(http_build_query([
                    'csrf' => html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'),
                    'approve' => '1',
                ], '', '&', PHP_QUERY_RFC3986)));
            $approveResponse = KirbyMcpOAuthRoute::handle($projectRoot, $approveRequest);
            expect($approveResponse->code())->toBe(302);
            $location = kirbyMcpRouteLocation($approveResponse);
            expect($location)->toStartWith('https://claude.ai/api/mcp/auth_callback?');
            parse_str((string) parse_url($location, PHP_URL_QUERY), $redirectQuery);
            expect($redirectQuery['state'] ?? null)->toBe('state-123')
                ->and($redirectQuery['code'] ?? null)->toBeString();

            $tokenBody = http_build_query([
                'grant_type' => 'authorization_code',
                'client_id' => $client['client_id'],
                'code' => $redirectQuery['code'],
                'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
                'code_verifier' => $verifier,
            ], '', '&', PHP_QUERY_RFC3986);
            $tokenRequest = $factory->createServerRequest('POST', 'https://example.test/mcp/oauth/token', [
                'REMOTE_ADDR' => '203.0.113.10',
            ])
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody($factory->createStream($tokenBody));
            $tokenResponse = KirbyMcpOAuthRoute::handle($projectRoot, $tokenRequest);
            $token = kirbyMcpRouteDecodeJson($tokenResponse->body());
            expect($tokenResponse->code())->toBe(200)
                ->and($token['token_type'] ?? null)->toBe('Bearer')
                ->and($token['access_token'] ?? null)->toBeString()
                ->and($token['refresh_token'] ?? null)->toBeString()
                ->and($token['scope'] ?? null)->toBe('kirby-mcp:read kirby-mcp:runtime');

            $invalidRefreshRequest = $factory->createServerRequest('POST', 'https://example.test/mcp/oauth/token', [
                'REMOTE_ADDR' => '203.0.113.10',
            ])
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody($factory->createStream(http_build_query([
                    'grant_type' => 'refresh_token',
                    'client_id' => $client['client_id'],
                    'refresh_token' => $token['refresh_token'],
                    'scope' => 'not-a-scope',
                ], '', '&', PHP_QUERY_RFC3986)));
            $invalidRefreshResponse = KirbyMcpOAuthRoute::handle($projectRoot, $invalidRefreshRequest);
            $invalidRefresh = kirbyMcpRouteDecodeJson($invalidRefreshResponse->body());
            expect($invalidRefreshResponse->code())->toBe(400)
                ->and($invalidRefresh['error'] ?? null)->toBe('invalid_scope');

            $mcpRequest = $factory->createServerRequest('POST', 'https://example.test/mcp', [
                'REMOTE_ADDR' => '203.0.113.10',
            ])
                ->withHeader('Authorization', 'Bearer ' . $token['access_token'])
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(kirbyMcpRouteInitializePayload()));
            $mcpResponse = KirbyMcpRoute::handle($projectRoot, $mcpRequest);
            $mcpPayload = kirbyMcpRouteDecodeJson($mcpResponse->body());
            expect($mcpResponse->code())->toBe(200)
                ->and($mcpResponse->headers()['Mcp-Session-Id'] ?? '')->not()->toBe('')
                ->and($mcpPayload['result']['serverInfo']['name'] ?? null)->toBe('Kirby MCP');
        });
    } finally {
        kirbyMcpRouteCommitSession($app);
        $app->impersonate(null);
        if ($previousApp instanceof App) {
            App::instance($previousApp);
        }
        App::$enableWhoops = $previousWhoops;
        restoreErrorHandlers($previousErrorHandlers);
        kirbyMcpRouteRemoveDirectory($oauthStorage);
    }
});

it('forces explicit consent when an authorize flow is resumed from a login session', function (): void {
    $projectRoot = cmsPath();
    $oauthStorage = $projectRoot . DIRECTORY_SEPARATOR . '.kirby-mcp' . DIRECTORY_SEPARATOR . 'oauth';
    kirbyMcpRouteRemoveDirectory($oauthStorage);

    $previousApp = App::instance(null, true);
    $previousErrorHandlers = captureErrorHandlers();
    $previousWhoops = App::$enableWhoops;
    App::$enableWhoops = false;
    $app = new App([
        'roots' => [
            'index' => $projectRoot,
        ],
    ]);
    ensureUser($app, 'mcp-oauth-fixation@example.com');

    try {
        kirbyMcpRouteWithHttpEnv([
            'KIRBY_MCP_HTTP_ENABLED' => '1',
            'KIRBY_MCP_HTTP_AUTH_MODE' => 'oauth',
            'KIRBY_MCP_HTTP_OAUTH_PROVIDER_ENABLED' => '1',
            'KIRBY_MCP_HTTP_OAUTH_PROVIDER_CONSENT' => 'auto',
            'KIRBY_MCP_HTTP_SCOPES' => 'kirby-mcp:read,kirby-mcp:runtime',
        ], function () use ($projectRoot, $app): void {
            $factory = new HttpFactory();

            $registerRequest = $factory->createServerRequest('POST', 'https://example.test/mcp/oauth/register', [
                'REMOTE_ADDR' => '203.0.113.10',
            ])
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(json_encode([
                    'client_name' => 'Evil Connector',
                    'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback'],
                    'token_endpoint_auth_method' => 'none',
                    'scope' => 'kirby-mcp:read kirby-mcp:runtime',
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)));
            $client = kirbyMcpRouteDecodeJson(KirbyMcpOAuthRoute::handle($projectRoot, $registerRequest)->body());

            $verifier = str_repeat('a', 43);
            $challenge = OAuthKeySet::base64Url(hash('sha256', $verifier, true));
            $authorizeQuery = http_build_query([
                'response_type' => 'code',
                'client_id' => $client['client_id'],
                'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
                'scope' => 'kirby-mcp:read kirby-mcp:runtime',
                'state' => 'state-fix',
                'resource' => 'https://example.test/mcp',
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ], '', '&', PHP_QUERY_RFC3986);

            // Step 1: an unauthenticated authorize request stores the params and
            // redirects to login with a session id (the attacker-seeded session).
            $app->impersonate(null);
            $unauthRequest = $factory->createServerRequest('GET', 'https://example.test/mcp/oauth/authorize?' . $authorizeQuery, [
                'REMOTE_ADDR' => '203.0.113.10',
            ]);
            $unauthResponse = KirbyMcpOAuthRoute::handle($projectRoot, $unauthRequest);
            expect($unauthResponse->code())->toBe(302);
            $loginLocation = kirbyMcpRouteLocation($unauthResponse);
            parse_str((string) parse_url($loginLocation, PHP_URL_QUERY), $loginQuery);
            $sessionId = $loginQuery['session'] ?? null;
            expect($sessionId)->toBeString();

            // Step 2: the victim logs in and the flow resumes via ?session=.
            // Even with consent=auto, an explicit consent screen must be shown
            // (no silent 302 code redirect to the attacker's redirect_uri).
            $app->impersonate('mcp-oauth-fixation@example.com');
            $resumeRequest = $factory->createServerRequest('GET', 'https://example.test/mcp/oauth/authorize?' . http_build_query([
                'session' => $sessionId,
            ], '', '&', PHP_QUERY_RFC3986), [
                'REMOTE_ADDR' => '203.0.113.10',
            ]);
            $resumeResponse = KirbyMcpOAuthRoute::handle($projectRoot, $resumeRequest);

            expect($resumeResponse->code())->toBe(200);
            expect($resumeResponse->body())->toContain('Authorize Evil Connector');
        });
    } finally {
        kirbyMcpRouteCommitSession($app);
        $app->impersonate(null);
        if ($previousApp instanceof App) {
            App::instance($previousApp);
        }
        App::$enableWhoops = $previousWhoops;
        restoreErrorHandlers($previousErrorHandlers);
        kirbyMcpRouteRemoveDirectory($oauthStorage);
    }
});

it('denies OAuth authorization for Panel users below the configured role', function (): void {
    $projectRoot = cmsPath();
    $oauthStorage = $projectRoot . DIRECTORY_SEPARATOR . '.kirby-mcp' . DIRECTORY_SEPARATOR . 'oauth';
    kirbyMcpRouteRemoveDirectory($oauthStorage);

    $previousApp = App::instance(null, true);
    $previousErrorHandlers = captureErrorHandlers();
    $previousWhoops = App::$enableWhoops;
    App::$enableWhoops = false;
    $app = new App([
        'roots' => [
            'index' => $projectRoot,
        ],
        'blueprints' => [
            'users/editor' => ['name' => 'editor', 'title' => 'Editor'],
        ],
    ]);

    $app->impersonate('kirby', function () use ($app): void {
        if ($app->user('mcp-oauth-editor@example.com') === null) {
            $app->users()->create([
                'email' => 'mcp-oauth-editor@example.com',
                'password' => 'test1234',
                'role' => 'editor',
            ]);
        }
    });
    $app->impersonate('mcp-oauth-editor@example.com');

    try {
        kirbyMcpRouteWithHttpEnv([
            'KIRBY_MCP_HTTP_ENABLED' => '1',
            'KIRBY_MCP_HTTP_AUTH_MODE' => 'oauth',
            'KIRBY_MCP_HTTP_OAUTH_PROVIDER_ENABLED' => '1',
            'KIRBY_MCP_HTTP_SCOPES' => 'kirby-mcp:read,kirby-mcp:runtime',
        ], function () use ($projectRoot): void {
            $factory = new HttpFactory();

            $registerRequest = $factory->createServerRequest('POST', 'https://example.test/mcp/oauth/register', [
                'REMOTE_ADDR' => '203.0.113.10',
            ])
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream(json_encode([
                    'client_name' => 'Claude Desktop',
                    'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback'],
                    'token_endpoint_auth_method' => 'none',
                    'scope' => 'kirby-mcp:read kirby-mcp:runtime',
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)));
            $registerResponse = KirbyMcpOAuthRoute::handle($projectRoot, $registerRequest);
            $client = kirbyMcpRouteDecodeJson($registerResponse->body());
            expect($registerResponse->code())->toBe(201);

            $verifier = str_repeat('a', 43);
            $challenge = OAuthKeySet::base64Url(hash('sha256', $verifier, true));
            $authorizeQuery = http_build_query([
                'response_type' => 'code',
                'client_id' => $client['client_id'],
                'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
                'scope' => 'kirby-mcp:read kirby-mcp:runtime',
                'state' => 'state-deny',
                'resource' => 'https://example.test/mcp',
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
            ], '', '&', PHP_QUERY_RFC3986);
            $authorizeRequest = $factory->createServerRequest('GET', 'https://example.test/mcp/oauth/authorize?' . $authorizeQuery, [
                'REMOTE_ADDR' => '203.0.113.10',
            ]);
            $authorizeResponse = KirbyMcpOAuthRoute::handle($projectRoot, $authorizeRequest);

            // A non-admin Panel user must not be able to authorize / mint tokens.
            expect($authorizeResponse->code())->toBe(302);
            $location = kirbyMcpRouteLocation($authorizeResponse);
            expect($location)->toStartWith('https://claude.ai/api/mcp/auth_callback?');
            parse_str((string) parse_url($location, PHP_URL_QUERY), $redirectQuery);
            expect($redirectQuery['error'] ?? null)->toBe('access_denied')
                ->and($redirectQuery['state'] ?? null)->toBe('state-deny')
                ->and($redirectQuery)->not()->toHaveKey('code');
        });
    } finally {
        // Delete the editor without the closure form of impersonate(): the
        // closure restores the *previous* impersonated user afterwards, which
        // would be the editor we just deleted.
        $app->impersonate('kirby');
        $app->user('mcp-oauth-editor@example.com')?->delete();
        $app->impersonate(null);
        kirbyMcpRouteCommitSession($app);
        if ($previousApp instanceof App) {
            App::instance($previousApp);
        }
        App::$enableWhoops = $previousWhoops;
        restoreErrorHandlers($previousErrorHandlers);
        kirbyMcpRouteRemoveDirectory($oauthStorage);
    }
});

it('preserves OAuth query params when explicit consent posts back for a logged-in user', function (): void {
    $projectRoot = cmsPath();
    $oauthStorage = $projectRoot . DIRECTORY_SEPARATOR . '.kirby-mcp' . DIRECTORY_SEPARATOR . 'oauth';
    kirbyMcpRouteRemoveDirectory($oauthStorage);

    $previousApp = App::instance(null, true);
    $previousErrorHandlers = captureErrorHandlers();
    $previousWhoops = App::$enableWhoops;
    App::$enableWhoops = false;
    $app = new App([
        'roots' => [
            'index' => $projectRoot,
        ],
    ]);
    ensureUser($app, 'mcp-oauth-consent@example.com');
    $app->impersonate('mcp-oauth-consent@example.com');

    try {
        kirbyMcpRouteWithHttpEnv([
            'KIRBY_MCP_HTTP_ENABLED' => '1',
            'KIRBY_MCP_HTTP_AUTH_MODE' => 'oauth',
            'KIRBY_MCP_HTTP_OAUTH_PROVIDER_ENABLED' => '1',
            'KIRBY_MCP_HTTP_OAUTH_PROVIDER_CONSENT' => 'always',
            'KIRBY_MCP_HTTP_SCOPES' => 'kirby-mcp:read,kirby-mcp:runtime',
        ], function () use ($projectRoot): void {
            $factory = new HttpFactory();
            $client = kirbyMcpRouteRegisterOAuthClient($factory, $projectRoot, [
                'scope' => 'kirby-mcp:read',
            ]);
            $verifier = str_repeat('b', 43);
            $authorizeQuery = http_build_query([
                'response_type' => 'code',
                'client_id' => $client['client_id'],
                'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
                'scope' => 'kirby-mcp:read',
                'state' => 'manual-consent',
                'resource' => 'https://example.test/mcp',
                'code_challenge' => OAuthKeySet::base64Url(hash('sha256', $verifier, true)),
                'code_challenge_method' => 'S256',
            ], '', '&', PHP_QUERY_RFC3986);
            $authorizeUrl = 'https://example.test/mcp/oauth/authorize?' . $authorizeQuery;

            $authorizeResponse = KirbyMcpOAuthRoute::handle($projectRoot, $factory->createServerRequest('GET', $authorizeUrl, [
                'REMOTE_ADDR' => '203.0.113.10',
            ]));
            expect($authorizeResponse->code())->toBe(200);
            expect(preg_match('/name="csrf" value="([^"]*)"/', $authorizeResponse->body(), $matches))->toBe(1);

            $approveRequest = $factory->createServerRequest('POST', $authorizeUrl, [
                'REMOTE_ADDR' => '203.0.113.10',
            ])
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody($factory->createStream(http_build_query([
                    'csrf' => html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'),
                    'approve' => '1',
                ], '', '&', PHP_QUERY_RFC3986)));
            $approveResponse = KirbyMcpOAuthRoute::handle($projectRoot, $approveRequest);
            expect($approveResponse->code())->toBe(302);
            parse_str((string) parse_url(kirbyMcpRouteLocation($approveResponse), PHP_URL_QUERY), $redirectQuery);
            expect($redirectQuery['state'] ?? null)->toBe('manual-consent')
                ->and($redirectQuery['code'] ?? null)->toBeString();
        });
    } finally {
        kirbyMcpRouteCommitSession($app);
        $app->impersonate(null);
        if ($previousApp instanceof App) {
            App::instance($previousApp);
        }
        App::$enableWhoops = $previousWhoops;
        restoreErrorHandlers($previousErrorHandlers);
        kirbyMcpRouteRemoveDirectory($oauthStorage);
    }
});

it('renders custom OAuth consent snippets with approval data', function (): void {
    $projectRoot = cmsPath();
    $oauthStorage = $projectRoot . DIRECTORY_SEPARATOR . '.kirby-mcp' . DIRECTORY_SEPARATOR . 'oauth';
    $snippetFile = $projectRoot . DIRECTORY_SEPARATOR . 'site' . DIRECTORY_SEPARATOR . 'snippets' . DIRECTORY_SEPARATOR . 'mcp-oauth-consent-test.php';
    $snippetBackup = is_file($snippetFile) ? file_get_contents($snippetFile) : null;
    kirbyMcpRouteRemoveDirectory($oauthStorage);
    file_put_contents($snippetFile, <<<'PHP'
<?php
$clientName = (string) ($client['client_name'] ?? $client['client_id'] ?? 'OAuth client');
$userEmail = (string) ($user?->email() ?? 'Kirby user');
?>
<form data-oauth-consent method="post" action="<?= esc((string) $approveUrl, 'attr') ?>">
    <?php if ($error !== null): ?>
    <p data-error><?= esc((string) $error) ?></p>
    <?php endif ?>
    <strong data-client><?= esc($clientName) ?></strong>
    <span data-user><?= esc($userEmail) ?></span>
    <?php foreach ($scopes as $scope): ?>
    <span data-scope><?= esc((string) $scope) ?></span>
    <?php endforeach ?>
    <input type="hidden" name="csrf" value="<?= esc((string) csrf(), 'attr') ?>">
    <button type="submit" name="approve" value="1">Approve</button>
    <button type="submit" name="deny" value="1" formaction="<?= esc((string) $denyUrl, 'attr') ?>">Deny</button>
</form>
PHP);

    $previousApp = App::instance(null, true);
    $previousErrorHandlers = captureErrorHandlers();
    $previousWhoops = App::$enableWhoops;
    App::$enableWhoops = false;
    $app = new App([
        'roots' => [
            'index' => $projectRoot,
        ],
    ]);
    ensureUser($app, 'mcp-oauth-snippet@example.com');
    $app->impersonate('mcp-oauth-snippet@example.com');

    try {
        kirbyMcpRouteWithHttpEnv([
            'KIRBY_MCP_HTTP_ENABLED' => '1',
            'KIRBY_MCP_HTTP_AUTH_MODE' => 'oauth',
            'KIRBY_MCP_HTTP_OAUTH_PROVIDER_ENABLED' => '1',
            'KIRBY_MCP_HTTP_OAUTH_PROVIDER_CONSENT' => 'snippet',
            'KIRBY_MCP_HTTP_OAUTH_PROVIDER_CONSENT_SNIPPET' => 'mcp-oauth-consent-test',
            'KIRBY_MCP_HTTP_SCOPES' => 'kirby-mcp:read,kirby-mcp:runtime',
        ], function () use ($projectRoot): void {
            $factory = new HttpFactory();
            $client = kirbyMcpRouteRegisterOAuthClient($factory, $projectRoot, [
                'scope' => 'kirby-mcp:read',
            ]);
            $verifier = str_repeat('s', 43);
            $authorizeQuery = http_build_query([
                'response_type' => 'code',
                'client_id' => $client['client_id'],
                'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
                'scope' => 'kirby-mcp:read',
                'state' => 'snippet-consent',
                'resource' => 'https://example.test/mcp',
                'code_challenge' => OAuthKeySet::base64Url(hash('sha256', $verifier, true)),
                'code_challenge_method' => 'S256',
            ], '', '&', PHP_QUERY_RFC3986);
            $authorizeUrl = 'https://example.test/mcp/oauth/authorize?' . $authorizeQuery;

            $authorizeResponse = KirbyMcpOAuthRoute::handle($projectRoot, $factory->createServerRequest('GET', $authorizeUrl, [
                'REMOTE_ADDR' => '203.0.113.10',
            ]));
            expect($authorizeResponse->code())->toBe(200);
            expect($authorizeResponse->body())
                ->toContain('data-oauth-consent')
                ->toContain('Claude Desktop')
                ->toContain('mcp-oauth-snippet@example.com')
                ->toContain('kirby-mcp:read')
                ->toContain('snippet-consent');
            expect(preg_match('/name="csrf" value="([^"]*)"/', $authorizeResponse->body(), $matches))->toBe(1);

            $approveRequest = $factory->createServerRequest('POST', $authorizeUrl, [
                'REMOTE_ADDR' => '203.0.113.10',
            ])
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody($factory->createStream(http_build_query([
                    'csrf' => html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'),
                    'approve' => '1',
                ], '', '&', PHP_QUERY_RFC3986)));
            $approveResponse = KirbyMcpOAuthRoute::handle($projectRoot, $approveRequest);
            expect($approveResponse->code())->toBe(302);
            parse_str((string) parse_url(kirbyMcpRouteLocation($approveResponse), PHP_URL_QUERY), $redirectQuery);
            expect($redirectQuery['state'] ?? null)->toBe('snippet-consent')
                ->and($redirectQuery['code'] ?? null)->toBeString();
        });
    } finally {
        kirbyMcpRouteCommitSession($app);
        $app->impersonate(null);
        if ($previousApp instanceof App) {
            App::instance($previousApp);
        }
        App::$enableWhoops = $previousWhoops;
        restoreErrorHandlers($previousErrorHandlers);
        kirbyMcpRouteRemoveDirectory($oauthStorage);
        if (is_string($snippetBackup)) {
            file_put_contents($snippetFile, $snippetBackup);
        } else {
            @unlink($snippetFile);
        }
    }
});

it('rejects authorize scopes that exceed the registered OAuth client scope', function (): void {
    $projectRoot = cmsPath();
    $oauthStorage = $projectRoot . DIRECTORY_SEPARATOR . '.kirby-mcp' . DIRECTORY_SEPARATOR . 'oauth';
    kirbyMcpRouteRemoveDirectory($oauthStorage);

    try {
        kirbyMcpRouteWithHttpEnv([
            'KIRBY_MCP_HTTP_ENABLED' => '1',
            'KIRBY_MCP_HTTP_AUTH_MODE' => 'oauth',
            'KIRBY_MCP_HTTP_OAUTH_PROVIDER_ENABLED' => '1',
            'KIRBY_MCP_HTTP_SCOPES' => 'kirby-mcp:read,kirby-mcp:runtime',
        ], function () use ($projectRoot): void {
            $factory = new HttpFactory();
            $client = kirbyMcpRouteRegisterOAuthClient($factory, $projectRoot, [
                'scope' => 'kirby-mcp:read',
            ]);
            $authorizeQuery = http_build_query([
                'response_type' => 'code',
                'client_id' => $client['client_id'],
                'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
                'scope' => 'kirby-mcp:read kirby-mcp:runtime',
                'state' => 'scope-check',
                'resource' => 'https://example.test/mcp',
                'code_challenge' => OAuthKeySet::base64Url(hash('sha256', str_repeat('c', 43), true)),
                'code_challenge_method' => 'S256',
            ], '', '&', PHP_QUERY_RFC3986);

            $response = KirbyMcpOAuthRoute::handle($projectRoot, $factory->createServerRequest('GET', 'https://example.test/mcp/oauth/authorize?' . $authorizeQuery, [
                'REMOTE_ADDR' => '203.0.113.10',
            ]));

            expect($response->code())->toBe(302);
            parse_str((string) parse_url(kirbyMcpRouteLocation($response), PHP_URL_QUERY), $redirectQuery);
            expect($redirectQuery['state'] ?? null)->toBe('scope-check')
                ->and($redirectQuery['error'] ?? null)->toBe('invalid_scope')
                ->and($redirectQuery['error_description'] ?? '')->toContain('kirby-mcp:runtime');
        });
    } finally {
        kirbyMcpRouteRemoveDirectory($oauthStorage);
    }
});

it('decodes OAuth JSON request data from the raw PSR-7 body before parsed body data', function (): void {
    $projectRoot = cmsPath();
    $oauthStorage = $projectRoot . DIRECTORY_SEPARATOR . '.kirby-mcp' . DIRECTORY_SEPARATOR . 'oauth';
    kirbyMcpRouteRemoveDirectory($oauthStorage);

    try {
        kirbyMcpRouteWithHttpEnv([
            'KIRBY_MCP_HTTP_ENABLED' => '1',
            'KIRBY_MCP_HTTP_AUTH_MODE' => 'oauth',
            'KIRBY_MCP_HTTP_OAUTH_PROVIDER_ENABLED' => '1',
        ], function () use ($projectRoot): void {
            $factory = new HttpFactory();
            $request = $factory->createServerRequest('POST', 'https://example.test/mcp/oauth/register', [
                'REMOTE_ADDR' => '203.0.113.10',
            ])
                ->withHeader('Content-Type', 'application/json')
                ->withParsedBody([
                    'client_name' => 'Parsed Body Should Not Win',
                    'redirect_uris' => ['https://wrong.example/callback'],
                ])
                ->withBody($factory->createStream(json_encode([
                    'client_name' => 'Claude Desktop',
                    'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback'],
                    'token_endpoint_auth_method' => 'none',
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)));

            $response = KirbyMcpOAuthRoute::handle($projectRoot, $request);
            $payload = kirbyMcpRouteDecodeJson($response->body());

            expect($response->code())->toBe(201)
                ->and($payload['client_name'] ?? null)->toBe('Claude Desktop')
                ->and($payload['redirect_uris'] ?? null)->toBe(['https://claude.ai/api/mcp/auth_callback']);
        });
    } finally {
        kirbyMcpRouteRemoveDirectory($oauthStorage);
    }
});

it('rejects malformed OAuth JSON bodies instead of falling back to parsed body data', function (): void {
    kirbyMcpRouteWithHttpEnv([
        'KIRBY_MCP_HTTP_ENABLED' => '1',
        'KIRBY_MCP_HTTP_AUTH_MODE' => 'oauth',
        'KIRBY_MCP_HTTP_OAUTH_PROVIDER_ENABLED' => '1',
    ], function (): void {
        $factory = new HttpFactory();
        $request = $factory->createServerRequest('POST', 'https://example.test/mcp/oauth/register', [
            'REMOTE_ADDR' => '203.0.113.10',
        ])
            ->withHeader('Content-Type', 'application/json')
            ->withParsedBody([
                'client_name' => 'Parsed Body Should Not Win',
                'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback'],
            ])
            ->withBody($factory->createStream('{not valid json'));

        $response = KirbyMcpOAuthRoute::handle(cmsPath(), $request);
        $payload = kirbyMcpRouteDecodeJson($response->body());

        expect($response->code())->toBe(400)
            ->and($payload['error'] ?? null)->toBe('invalid_request')
            ->and($payload['error_description'] ?? '')->toContain('valid JSON object');
    });
});

it('rejects invalid remote-token bearer credentials', function (): void {
    kirbyMcpRouteWithHttpEnv([
        'KIRBY_MCP_HTTP_ENABLED' => '1',
        'KIRBY_MCP_HTTP_AUTH_MODE' => 'remote-token',
        'KIRBY_MCP_HTTP_REMOTE_TOKEN' => 'remote-secret',
    ], function (): void {
        $factory = new HttpFactory();
        $request = $factory->createServerRequest('POST', 'https://example.test/mcp', [
            'REMOTE_ADDR' => '203.0.113.10',
        ])
            ->withHeader('Authorization', 'Bearer wrong-secret')
            ->withHeader('Content-Type', 'application/json')
            ->withBody($factory->createStream(kirbyMcpRouteInitializePayload()));

        $response = KirbyMcpRoute::handle(cmsPath(), $request);

        expect($response->code())->toBe(401);
        expect($response->body())->toContain('invalid_token');
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
