<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Bnomei\KirbyMcp\Mcp\OAuth\KirbyOAuthProvider;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Bnomei\KirbyMcp\Project\KirbyMcpHttpConfig;
use Bnomei\KirbyMcp\Project\ProjectRootFinder;
use GuzzleHttp\Psr7\ServerRequest;
use Kirby\Cms\App as Kirby;
use Kirby\Http\Response as KirbyResponse;
use Psr\Http\Message\ServerRequestInterface;

final class KirbyMcpOAuthRoute
{
    public static function handle(
        ?string $projectRoot = null,
        ?ServerRequestInterface $request = null,
    ): KirbyResponse {
        $request ??= ServerRequest::fromGlobals();
        $projectRoot = self::resolveProjectRoot($projectRoot);
        if ($projectRoot === null) {
            return self::error(500, 'Unable to determine Kirby project root.');
        }

        $projectConfig = KirbyMcpConfig::load($projectRoot);
        if ($projectConfig->error !== null) {
            return self::error(503, 'HTTP MCP config could not be read.', [
                'detail' => $projectConfig->error,
            ]);
        }

        $config = self::withResolvedOAuthProvider($projectConfig->http(), $request);
        if ($config->enabled === false || $config->authMode !== KirbyMcpHttpConfig::AUTH_MODE_OAUTH || $config->oauthProvider->enabled === false) {
            return self::error(404, 'HTTP OAuth provider route is disabled.');
        }

        $errors = self::validationErrors($config);
        if ($errors !== []) {
            return self::error(503, 'HTTP OAuth provider route is not configured correctly.', [
                'errors' => $errors,
            ]);
        }

        return (new KirbyOAuthProvider($projectRoot, $config, $request))->handle();
    }

    public static function withResolvedOAuthProvider(
        KirbyMcpHttpConfig $config,
        ServerRequestInterface $request,
    ): KirbyMcpHttpConfig {
        if ($config->authMode !== KirbyMcpHttpConfig::AUTH_MODE_OAUTH || $config->oauthProvider->enabled === false) {
            return $config;
        }

        $baseUrl = self::baseUrl($request);

        return $config->withOAuthEndpoints(
            issuer: $baseUrl,
            audience: $baseUrl . $config->path,
            jwksUri: $baseUrl . rtrim($config->oauthProvider->path, '/') . '/jwks.json',
        );
    }

    private static function resolveProjectRoot(?string $projectRoot): ?string
    {
        $finder = new ProjectRootFinder();

        if (is_string($projectRoot) && trim($projectRoot) !== '') {
            $projectRoot = trim($projectRoot);
            $detected = $finder->findKirbyProjectRoot($projectRoot);

            if ($detected !== null) {
                return $detected;
            }

            return rtrim(realpath($projectRoot) ?: $projectRoot, DIRECTORY_SEPARATOR);
        }

        $kirbyRoot = Kirby::instance(lazy: true)?->root('index');
        $detected = $finder->findKirbyProjectRoot($kirbyRoot);

        return $detected ?? $finder->findKirbyProjectRoot();
    }

    private static function baseUrl(ServerRequestInterface $request): string
    {
        $uri = $request->getUri();
        $scheme = self::isHttpsRequest($request) ? 'https' : strtolower($uri->getScheme());
        if ($scheme === '') {
            $scheme = 'http';
        }

        $authority = $uri->getAuthority() !== '' ? $uri->getAuthority() : '127.0.0.1';

        return $scheme . '://' . $authority;
    }

    private static function isHttpsRequest(ServerRequestInterface $request): bool
    {
        if (strtolower($request->getUri()->getScheme()) === 'https') {
            return true;
        }

        $https = $request->getServerParams()['HTTPS'] ?? null;

        return is_string($https) && in_array(strtolower($https), ['1', 'on', 'true'], true);
    }

    /**
     * @return array<int, string>
     */
    private static function validationErrors(KirbyMcpHttpConfig $config): array
    {
        $listenerOnlyErrors = [
            'HTTP port must be between 1 and 65535.',
            'HTTP shared-token auth is only allowed for loopback hosts.',
            'Non-loopback HTTP binds require OAuth auth.',
        ];

        return array_values(array_diff($config->validationErrors(), $listenerOnlyErrors));
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function error(int $code, string $message, array $data = []): KirbyResponse
    {
        return KirbyResponse::json([
            'ok' => false,
            'error' => [
                'message' => $message,
                ...$data,
            ],
        ], $code);
    }
}
