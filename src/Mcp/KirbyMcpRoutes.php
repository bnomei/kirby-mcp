<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Bnomei\KirbyMcp\Project\KirbyMcpHttpConfig;
use Mcp\Server\Transport\Http\OAuth\ProtectedResourceMetadata;

final class KirbyMcpRoutes
{
    /**
     * Returns the copied Kirby route definitions needed for HTTP MCP.
     *
     * @return list<array{pattern: string, method: string, action: \Closure, name: string}>
     */
    public static function routes(
        string $path = KirbyMcpHttpConfig::DEFAULT_PATH,
        ?string $projectRoot = null,
        int $sseMaxSeconds = 300,
        string $oauthPath = '/mcp/oauth',
    ): array {
        return [
            ...self::mcp($path, $projectRoot, $sseMaxSeconds),
            ...self::oauth($projectRoot, $sseMaxSeconds, $oauthPath),
        ];
    }

    /**
     * Returns the copied Kirby route definition for the MCP endpoint.
     *
     * @return list<array{pattern: string, method: string, action: \Closure, name: string}>
     */
    public static function mcp(
        string $path = KirbyMcpHttpConfig::DEFAULT_PATH,
        ?string $projectRoot = null,
        int $sseMaxSeconds = 300,
    ): array {
        return [
            [
                'pattern' => self::patternFromPath($path),
                'method' => 'GET|POST|DELETE|OPTIONS',
                'action' => fn () => KirbyMcpRoute::handle($projectRoot, null, $sseMaxSeconds),
                'name' => 'kirby-mcp.mcp',
            ],
        ];
    }

    /**
     * Returns the copied Kirby route definitions needed for OAuth discovery.
     *
     * @return list<array{pattern: string, method: string, action: \Closure, name: string}>
     */
    public static function oauth(
        ?string $projectRoot = null,
        int $sseMaxSeconds = 300,
        string $oauthPath = '/mcp/oauth',
    ): array {
        $oauthPath = '/' . trim(self::patternFromPath($oauthPath), '/');

        return [
            ...self::oauthProtectedResourceMetadata($projectRoot, $sseMaxSeconds),
            [
                'pattern' => self::patternFromPath('/.well-known/oauth-authorization-server'),
                'method' => 'GET',
                'action' => fn () => KirbyMcpOAuthRoute::handle($projectRoot),
                'name' => 'kirby-mcp.oauth-authorization-server',
            ],
            [
                'pattern' => self::patternFromPath('/.well-known/openid-configuration'),
                'method' => 'GET',
                'action' => fn () => KirbyMcpOAuthRoute::handle($projectRoot),
                'name' => 'kirby-mcp.openid-configuration',
            ],
            [
                'pattern' => self::patternFromPath($oauthPath . '/register'),
                'method' => 'POST',
                'action' => fn () => KirbyMcpOAuthRoute::handle($projectRoot),
                'name' => 'kirby-mcp.oauth-register',
            ],
            [
                'pattern' => self::patternFromPath($oauthPath . '/authorize'),
                'method' => 'GET|POST',
                'action' => fn () => KirbyMcpOAuthRoute::handle($projectRoot),
                'name' => 'kirby-mcp.oauth-authorize',
            ],
            [
                'pattern' => self::patternFromPath($oauthPath . '/token'),
                'method' => 'POST',
                'action' => fn () => KirbyMcpOAuthRoute::handle($projectRoot),
                'name' => 'kirby-mcp.oauth-token',
            ],
            [
                'pattern' => self::patternFromPath($oauthPath . '/jwks.json'),
                'method' => 'GET',
                'action' => fn () => KirbyMcpOAuthRoute::handle($projectRoot),
                'name' => 'kirby-mcp.oauth-jwks',
            ],
            [
                'pattern' => self::patternFromPath($oauthPath . '/login'),
                'method' => 'GET|POST',
                'action' => fn () => KirbyMcpOAuthRoute::handle($projectRoot),
                'name' => 'kirby-mcp.oauth-login',
            ],
        ];
    }

    /**
     * Returns the copied Kirby route definition for OAuth protected resource metadata.
     *
     * @return list<array{pattern: string, method: string, action: \Closure, name: string}>
     */
    public static function oauthProtectedResourceMetadata(?string $projectRoot = null, int $sseMaxSeconds = 300): array
    {
        return [
            [
                'pattern' => self::patternFromPath(ProtectedResourceMetadata::DEFAULT_METADATA_PATH),
                'method' => 'GET',
                'action' => fn () => KirbyMcpRoute::handle($projectRoot, null, $sseMaxSeconds),
                'name' => 'kirby-mcp.oauth-protected-resource',
            ],
        ];
    }

    private static function patternFromPath(string $path): string
    {
        $path = trim($path);
        $parsedPath = parse_url($path, PHP_URL_PATH);
        if (is_string($parsedPath)) {
            $path = $parsedPath;
        }

        return trim($path, '/');
    }
}
