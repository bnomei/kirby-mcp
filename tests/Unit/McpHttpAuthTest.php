<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Http\HttpAuthFactory;
use Bnomei\KirbyMcp\Mcp\Http\HttpOriginPolicy;
use Bnomei\KirbyMcp\Mcp\Http\HttpAuthScopes;
use Bnomei\KirbyMcp\Mcp\Http\RemoteTokenValidator;
use Bnomei\KirbyMcp\Mcp\Http\SharedTokenValidator;
use Bnomei\KirbyMcp\Project\KirbyMcpHttpConfig;
use Bnomei\KirbyMcp\Project\KirbyMcpHttpToken;
use Mcp\Server\Transport\Http\OAuth\JwtTokenValidator;

it('validates shared bearer tokens with constant-time exact matches and scoped attributes', function (): void {
    $validator = new SharedTokenValidator('local-secret', [HttpAuthScopes::READ]);

    $allowed = $validator->validate('local-secret');
    expect($allowed->isAllowed())->toBeTrue()
        ->and($allowed->getAttributes()['oauth.scopes'] ?? null)->toBe([HttpAuthScopes::READ])
        ->and($allowed->getAttributes()['oauth.subject'] ?? null)->toBe('shared-token');

    $denied = $validator->validate('wrong-secret');
    expect($denied->isAllowed())->toBeFalse()
        ->and($denied->getStatusCode())->toBe(401)
        ->and($denied->getError())->toBe('invalid_token');
});

it('validates hashed remote bearer tokens with per-token scoped attributes', function (): void {
    $validator = new RemoteTokenValidator([
        KirbyMcpHttpToken::fromPlainText('claude-code', 'remote-secret', [HttpAuthScopes::READ]),
    ]);

    $allowed = $validator->validate('remote-secret');
    expect($allowed->isAllowed())->toBeTrue()
        ->and($allowed->getAttributes()['oauth.scopes'] ?? null)->toBe([HttpAuthScopes::READ])
        ->and($allowed->getAttributes()['oauth.subject'] ?? null)->toBe('remote-token:claude-code')
        ->and($allowed->getAttributes()['oauth.claims']['token_type'] ?? null)->toBe('remote-token')
        ->and($allowed->getAttributes()['oauth.claims']['token_id'] ?? null)->toBe('claude-code');

    $denied = $validator->validate('wrong-secret');
    expect($denied->isAllowed())->toBeFalse()
        ->and($denied->getStatusCode())->toBe(401)
        ->and($denied->getError())->toBe('invalid_token');
});

it('defaults remote tokens with empty scopes to read-only instead of all scopes', function (): void {
    $validator = new RemoteTokenValidator([
        KirbyMcpHttpToken::fromPlainText('ci-bot', 'remote-secret', []),
    ]);

    $allowed = $validator->validate('remote-secret');

    expect($allowed->isAllowed())->toBeTrue()
        ->and($allowed->getAttributes()['oauth.scopes'] ?? null)->toBe([HttpAuthScopes::READ])
        ->and($allowed->getAttributes()['oauth.scopes'] ?? null)->not()->toContain(HttpAuthScopes::ADMIN)
        ->and($allowed->getAttributes()['oauth.scopes'] ?? null)->not()->toContain(HttpAuthScopes::WRITE);
});

it('allows absent and default loopback Origin headers and rejects public origins without an allowlist', function (): void {
    $defaultPolicy = new HttpOriginPolicy();

    expect($defaultPolicy->allows(null))->toBeTrue()
        ->and($defaultPolicy->allows(''))->toBeTrue()
        ->and($defaultPolicy->allows('http://localhost:3000'))->toBeTrue()
        ->and($defaultPolicy->allows('http://127.0.0.1:5173'))->toBeTrue()
        ->and($defaultPolicy->allows('http://127.0.0.5:5173'))->toBeTrue()
        ->and($defaultPolicy->allows('http://[::1]:5173'))->toBeTrue()
        ->and($defaultPolicy->allows('http://example.test'))->toBeFalse()
        ->and($defaultPolicy->allows('http://127.0.0.1.evil.com'))->toBeFalse()
        ->and($defaultPolicy->allows('http://127.evil.com:8080'))->toBeFalse();
});

it('honors explicit Origin allowlists', function (): void {
    $policy = new HttpOriginPolicy(['http://127.0.0.1:3000']);

    expect($policy->allows(null))->toBeTrue()
        ->and($policy->allows(''))->toBeTrue()
        ->and($policy->allows('http://127.0.0.1:3000'))->toBeTrue()
        ->and($policy->allows('http://localhost:3000'))->toBeFalse()
        ->and($policy->allows('http://example.test'))->toBeFalse();
});

it('builds protected-resource metadata and OAuth JWT validators from HTTP config', function (): void {
    $factory = new HttpAuthFactory();
    $metadata = $factory->metadata('https://auth.example.test', 'https://kirby.example.test/mcp');

    expect($metadata->jsonSerialize()['authorization_servers'] ?? null)->toBe(['https://auth.example.test'])
        ->and($metadata->jsonSerialize()['resource'] ?? null)->toBe('https://kirby.example.test/mcp')
        ->and($metadata->getScopesSupported())->toBe(HttpAuthScopes::all());

    $validator = $factory->oauthValidator(new KirbyMcpHttpConfig(
        enabled: true,
        authMode: KirbyMcpHttpConfig::AUTH_MODE_OAUTH,
        oauthIssuer: 'https://auth.example.test',
        oauthAudience: 'https://kirby.example.test/mcp',
        oauthJwksUri: 'https://auth.example.test/.well-known/jwks.json',
    ));

    expect($validator)->toBeInstanceOf(JwtTokenValidator::class);
});

it('builds remote-token validators from token records', function (): void {
    $factory = new HttpAuthFactory();
    $validator = $factory->remoteTokenValidator([
        KirbyMcpHttpToken::fromPlainText('remote', 'secret'),
    ]);

    expect($validator)->toBeInstanceOf(RemoteTokenValidator::class);
});
