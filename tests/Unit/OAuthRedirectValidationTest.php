<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\OAuth\KirbyOAuthProvider;
use Bnomei\KirbyMcp\Project\KirbyMcpHttpConfig;
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
