<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Http;

use Bnomei\KirbyMcp\Mcp\OAuth\OAuthFileStore;
use Bnomei\KirbyMcp\Mcp\OAuth\OAuthKeySet;
use Bnomei\KirbyMcp\Project\KirbyMcpHttpConfig;
use Bnomei\KirbyMcp\Project\KirbyMcpHttpToken;
use Firebase\JWT\CachedKeySet;
use Firebase\JWT\JWK;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Mcp\Server\Transport\Http\OAuth\AuthorizationTokenValidatorInterface;
use Mcp\Server\Transport\Http\OAuth\JwtTokenValidator;
use Mcp\Server\Transport\Http\OAuth\ProtectedResourceMetadata;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final readonly class HttpAuthFactory
{
    public function metadata(string $issuer, string $resource): ProtectedResourceMetadata
    {
        return new ProtectedResourceMetadata(
            authorizationServers: [$issuer],
            scopesSupported: HttpAuthScopes::all(),
            resource: $resource,
            resourceName: 'Kirby MCP',
        );
    }

    public function sharedTokenValidator(string $sharedToken, array $scopes = []): AuthorizationTokenValidatorInterface
    {
        return new SharedTokenValidator($sharedToken, $this->normalizedScopes($scopes));
    }

    /**
     * @param list<KirbyMcpHttpToken> $tokens
     */
    public function remoteTokenValidator(array $tokens): AuthorizationTokenValidatorInterface
    {
        return new RemoteTokenValidator($tokens);
    }

    public function oauthValidator(KirbyMcpHttpConfig $config): AuthorizationTokenValidatorInterface
    {
        $cache = new ArrayAdapter();
        $client = new Client(['timeout' => 10, 'allow_redirects' => false]);
        $requests = new HttpFactory();
        if ($config->oauthJwksUri === null) {
            return JwtTokenValidator::fromIssuer(
                issuer: (string) $config->oauthIssuer,
                audience: (string) $config->oauthAudience,
                cache: $cache,
                httpClient: $client,
                requestFactory: $requests,
                algorithms: ['RS256', 'RS384', 'RS512'],
            );
        }

        foreach ([(string) $config->oauthIssuer, $config->oauthJwksUri] as $url) {
            $parts = parse_url($url);
            $host = strtolower(trim($parts['host'] ?? '', '[]'));
            $scheme = strtolower($parts['scheme'] ?? '');
            if ($host === '' || ($scheme !== 'https' && ($scheme !== 'http' || !in_array($host, ['localhost', '127.0.0.1', '::1'], true)))) {
                throw new \InvalidArgumentException('OAuth issuer and JWKS URI must use HTTPS (except loopback).');
            }
        }

        return new JwtTokenValidator(
            issuer: (string) $config->oauthIssuer,
            audience: (string) $config->oauthAudience,
            keys: new CachedKeySet($config->oauthJwksUri, $client, $requests, $cache, 3600, true, 'RS256'),
            algorithms: ['RS256', 'RS384', 'RS512'],
        );
    }

    public function oauthProviderValidator(KirbyMcpHttpConfig $config, string $projectRoot): AuthorizationTokenValidatorInterface
    {
        $store = new OAuthFileStore(
            rtrim($projectRoot, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . '.kirby-mcp'
            . DIRECTORY_SEPARATOR . 'oauth',
        );

        return new JwtTokenValidator(
            issuer: (string) $config->oauthIssuer,
            audience: (string) $config->oauthAudience,
            keys: JWK::parseKeySet((new OAuthKeySet($store))->jwks(), 'RS256'),
            algorithms: ['RS256'],
        );
    }

    /**
     * @return list<string>
     */
    private function normalizedScopes(array $scopes): array
    {
        $normalized = [];
        foreach ($scopes as $scope) {
            if (!is_string($scope)) {
                continue;
            }

            $scope = trim($scope);
            if ($scope !== '') {
                $normalized[] = $scope;
            }
        }

        return array_values(array_unique($normalized));
    }
}
