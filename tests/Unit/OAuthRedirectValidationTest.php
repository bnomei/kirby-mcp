<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\OAuth\KirbyOAuthProvider;
use Bnomei\KirbyMcp\Project\KirbyMcpHttpConfig;
use Bnomei\KirbyMcp\Project\KirbyMcpOAuthProviderConfig;
use GuzzleHttp\Psr7\HttpFactory;

function oauthRedirectProvider(): KirbyOAuthProvider
{
    $request = (new HttpFactory())->createServerRequest('GET', 'https://example.test/');

    return new KirbyOAuthProvider('/tmp', new KirbyMcpHttpConfig(), $request);
}

function oauthRedirectIsValid(array $uris): bool
{
    $method = new ReflectionMethod(KirbyOAuthProvider::class, 'redirectUrisAreValid');

    return (bool) $method->invoke(oauthRedirectProvider(), $uris);
}

it('accepts HTTPS and real loopback HTTP redirect URIs', function (): void {
    expect(oauthRedirectIsValid(['https://claude.ai/api/mcp/auth_callback']))->toBeTrue();
    expect(oauthRedirectIsValid(['http://127.0.0.1/cb']))->toBeTrue();
    expect(oauthRedirectIsValid(['http://127.5.6.7:8888/cb']))->toBeTrue();
    expect(oauthRedirectIsValid(['http://localhost/cb']))->toBeTrue();
    expect(oauthRedirectIsValid(['http://[::1]/cb']))->toBeTrue();
});

it('rejects deceptive 127.* hostnames that are not loopback addresses', function (): void {
    expect(oauthRedirectIsValid(['http://127.evil.example/cb']))->toBeFalse();
    expect(oauthRedirectIsValid(['http://127attacker.example/cb']))->toBeFalse();
    expect(oauthRedirectIsValid(['http://127.0.0.1.evil.example/cb']))->toBeFalse();
    expect(oauthRedirectIsValid(['http://evil.example/cb']))->toBeFalse();
});

it('requires HTTPS for OAuth provider requests unless both host and peer are loopback', function (string $host, string $peer, int $status): void {
    $request = (new HttpFactory())->createServerRequest('POST', 'http://' . $host . '/mcp/oauth/token', [
        'REMOTE_ADDR' => $peer,
    ]);
    $provider = new KirbyOAuthProvider('/tmp', new KirbyMcpHttpConfig(
        enabled: true,
        authMode: 'oauth',
        oauthProvider: new KirbyMcpOAuthProviderConfig(enabled: true),
    ), $request);

    // A transport-allowed request reaches the missing grant_type check (400).
    expect($provider->handle()->code())->toBe($status);
})->with([
    ['127.0.0.1.evil.example', '127.0.0.1', 503],
    ['127.evil.example', '127.0.0.1', 503],
    ['127.999.1.1', '127.0.0.1', 503],
    ['localhost', '203.0.113.5', 503],
    ['localhost', '127.not-an-ip', 503],
    ['localhost', '127.0.0.1', 400],
    ['127.5.6.7', '127.0.0.1', 400],
    ['[::1]', '::1', 400],
]);
