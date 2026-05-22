<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\KirbyMcpRoutes;

it('builds the copied Kirby route bundle for HTTP MCP', function (): void {
    $routes = KirbyMcpRoutes::routes();

    expect($routes)->toHaveCount(9)
        ->and($routes[0]['pattern'])->toBe('mcp')
        ->and($routes[0]['method'])->toBe('GET|POST|DELETE|OPTIONS')
        ->and($routes[0]['name'])->toBe('kirby-mcp.mcp')
        ->and($routes[0]['action'])->toBeInstanceOf(Closure::class)
        ->and($routes[1]['pattern'])->toBe('.well-known/oauth-protected-resource')
        ->and($routes[1]['method'])->toBe('GET')
        ->and($routes[1]['name'])->toBe('kirby-mcp.oauth-protected-resource')
        ->and($routes[1]['action'])->toBeInstanceOf(Closure::class)
        ->and($routes[2]['pattern'])->toBe('.well-known/oauth-authorization-server')
        ->and($routes[2]['name'])->toBe('kirby-mcp.oauth-authorization-server')
        ->and($routes[3]['pattern'])->toBe('.well-known/openid-configuration')
        ->and($routes[3]['name'])->toBe('kirby-mcp.openid-configuration')
        ->and($routes[4]['pattern'])->toBe('mcp/oauth/register')
        ->and($routes[4]['method'])->toBe('POST')
        ->and($routes[5]['pattern'])->toBe('mcp/oauth/authorize')
        ->and($routes[5]['method'])->toBe('GET|POST')
        ->and($routes[6]['pattern'])->toBe('mcp/oauth/token')
        ->and($routes[6]['method'])->toBe('POST')
        ->and($routes[7]['pattern'])->toBe('mcp/oauth/jwks.json')
        ->and($routes[7]['method'])->toBe('GET')
        ->and($routes[8]['pattern'])->toBe('mcp/oauth/login')
        ->and($routes[8]['method'])->toBe('GET|POST');
});

it('maps custom MCP URL paths to Kirby route patterns', function (): void {
    $routes = KirbyMcpRoutes::routes('/api/mcp?ignored=true');

    expect($routes[0]['pattern'])->toBe('api/mcp');
});

it('exposes focused route helpers', function (): void {
    expect(KirbyMcpRoutes::mcp('/custom')[0]['pattern'])->toBe('custom')
        ->and(KirbyMcpRoutes::oauth()[0]['pattern'])->toBe(KirbyMcpRoutes::oauthProtectedResourceMetadata()[0]['pattern']);
});

it('maps custom OAuth provider URL paths to Kirby route patterns', function (): void {
    $routes = KirbyMcpRoutes::oauth(oauthPath: '/api/oauth?ignored=true');

    expect($routes[4]['pattern'])->toBe('api/oauth/authorize')
        ->and($routes[6]['pattern'])->toBe('api/oauth/jwks.json');
});
