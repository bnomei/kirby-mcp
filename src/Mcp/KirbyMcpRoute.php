<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Bnomei\KirbyMcp\Mcp\Http\HttpAuthFactory;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Bnomei\KirbyMcp\Project\KirbyMcpHttpConfig;
use Bnomei\KirbyMcp\Project\ProjectRootFinder;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\ServerRequest;
use Kirby\Cms\App as Kirby;
use Kirby\Http\Response as KirbyResponse;
use Mcp\Server\Session\FileSessionStore;
use Psr\Http\Message\ServerRequestInterface;

final class KirbyMcpRoute
{
    public static function handle(
        ?string $projectRoot = null,
        ?ServerRequestInterface $request = null,
        int $sseMaxSeconds = 300,
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

        $config = $projectConfig->http();
        if ($config->enabled === false) {
            return self::error(404, 'HTTP MCP route is disabled.');
        }

        $errors = self::validationErrors($config);
        if ($errors !== []) {
            return self::error(503, 'HTTP MCP route is not configured correctly.', [
                'errors' => $errors,
            ]);
        }

        if (
            $config->authMode === KirbyMcpHttpConfig::AUTH_MODE_SHARED_TOKEN
            && self::isLoopbackRemoteAddress($request) === false
        ) {
            return self::error(503, 'HTTP shared-token auth is only allowed for loopback requests.');
        }

        putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $projectRoot);

        try {
            $handler = self::handler($projectRoot, $config, $sseMaxSeconds);
        } catch (\Throwable $exception) {
            return self::error(503, 'HTTP MCP route could not be started.', [
                'detail' => $exception->getMessage(),
            ]);
        }

        return new KirbyMcpResponse($handler->handle($request));
    }

    private static function handler(
        string $projectRoot,
        KirbyMcpHttpConfig $config,
        int $sseMaxSeconds,
    ): HttpMcpHandler {
        $sessionDir = rtrim($projectRoot, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . '.kirby-mcp'
            . DIRECTORY_SEPARATOR . 'http-sessions';

        $sessionStore = new FileSessionStore($sessionDir);
        $factory = new HttpFactory();
        $authFactory = new HttpAuthFactory();
        $sharedToken = null;
        $tokenValidator = null;
        $protectedResourceMetadata = null;

        if ($config->authMode === KirbyMcpHttpConfig::AUTH_MODE_SHARED_TOKEN && is_string($config->sharedToken)) {
            $sharedToken = $config->sharedToken;
            $tokenValidator = $authFactory->sharedTokenValidator($sharedToken, $config->scopes);
        } elseif ($config->authMode === KirbyMcpHttpConfig::AUTH_MODE_OAUTH && is_string($config->oauthIssuer) && is_string($config->oauthAudience)) {
            $tokenValidator = $authFactory->oauthValidator($config);
            $protectedResourceMetadata = $authFactory->metadata($config->oauthIssuer, $config->oauthAudience);
        }

        return new HttpMcpHandler(
            serverFactory: new ServerFactory(),
            sessionStore: $sessionStore,
            path: $config->path,
            responseFactory: $factory,
            streamFactory: $factory,
            sharedToken: $sharedToken,
            allowedOrigins: array_values($config->allowedOrigins),
            sseMaxSeconds: $sseMaxSeconds,
            tokenValidator: $tokenValidator,
            protectedResourceMetadata: $protectedResourceMetadata,
        );
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

    private static function isLoopbackRemoteAddress(ServerRequestInterface $request): bool
    {
        $remoteAddress = $request->getServerParams()['REMOTE_ADDR'] ?? null;
        if (!is_string($remoteAddress) || trim($remoteAddress) === '') {
            return false;
        }

        $remoteAddress = strtolower(trim($remoteAddress, " \t\n\r\0\x0B[]"));

        return $remoteAddress === '::1'
            || $remoteAddress === '127.0.0.1'
            || str_starts_with($remoteAddress, '127.');
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
