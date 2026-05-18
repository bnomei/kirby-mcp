<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Bnomei\KirbyMcp\Mcp\Http\HttpAuthFactory;
use Bnomei\KirbyMcp\Mcp\Http\HttpOriginPolicy;
use Bnomei\KirbyMcp\Mcp\Http\HttpScopeMiddleware;
use Bnomei\KirbyMcp\Mcp\Http\HttpScopePolicy;
use GuzzleHttp\Psr7\HttpFactory;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Schema\JsonRpc\MessageInterface;
use Mcp\Server\Session\SessionStoreInterface;
use Mcp\Server\Transport\Http\Middleware\AuthorizationMiddleware;
use Mcp\Server\Transport\Http\Middleware\OAuthRequestMetaMiddleware;
use Mcp\Server\Transport\Http\Middleware\ProtectedResourceMetadataMiddleware;
use Mcp\Server\Transport\Http\MiddlewareRequestHandler;
use Mcp\Server\Transport\Http\OAuth\AuthorizationResult;
use Mcp\Server\Transport\Http\OAuth\AuthorizationTokenValidatorInterface;
use Mcp\Server\Transport\Http\OAuth\ProtectedResourceMetadata;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Uid\Uuid;

final class HttpMcpHandler
{
    private const SESSION_HEADER = 'Mcp-Session-Id';

    /**
     * @param list<string> $allowedOrigins
     */
    public function __construct(
        private readonly ServerFactory $serverFactory,
        private readonly SessionStoreInterface $sessionStore,
        private readonly string $path = '/mcp',
        private readonly ?ResponseFactoryInterface $responseFactory = null,
        private readonly ?StreamFactoryInterface $streamFactory = null,
        private readonly ?string $sharedToken = null,
        private readonly array $allowedOrigins = [],
        private readonly int $sseMaxSeconds = 300,
        private readonly int $ssePollIntervalMicros = 100000,
        private readonly ?AuthorizationTokenValidatorInterface $tokenValidator = null,
        private readonly ?ProtectedResourceMetadata $protectedResourceMetadata = null,
        private readonly ?HttpScopePolicy $scopePolicy = null,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $responseFactory = $this->responseFactory ?? new HttpFactory();
        $streamFactory = $this->streamFactory ?? new HttpFactory();

        $originResponse = $this->validateOrigin($request, $responseFactory, $streamFactory);
        if ($originResponse instanceof ResponseInterface) {
            return $originResponse;
        }

        $metadata = $this->protectedResourceMetadata($request);
        if ($this->isProtectedResourceMetadataRequest($request, $metadata)) {
            return (new MiddlewareRequestHandler([
                new ProtectedResourceMetadataMiddleware($metadata, $responseFactory, $streamFactory),
            ], static fn (): ResponseInterface => $responseFactory->createResponse(404)))->handle($request);
        }

        if ($request->getUri()->getPath() !== $this->path) {
            return $this->withCorsHeaders($responseFactory->createResponse(404)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($streamFactory->createStream($this->encodeError('MCP endpoint not found.'))), $request);
        }

        $queryCredentialResponse = $this->rejectQueryCredentials($request, $responseFactory, $streamFactory);
        if ($queryCredentialResponse instanceof ResponseInterface) {
            return $this->withCorsHeaders($queryCredentialResponse, $request);
        }

        $handler = new MiddlewareRequestHandler([
            new AuthorizationMiddleware($this->authorizationTokenValidator(), $metadata, $responseFactory),
            new OAuthRequestMetaMiddleware($streamFactory),
            new HttpScopeMiddleware($this->scopePolicy ?? new HttpScopePolicy(), $responseFactory, $streamFactory),
        ], fn (ServerRequestInterface $request): ResponseInterface => $this->handleAuthorizedRequest(
            $request,
            $responseFactory,
            $streamFactory,
        ));

        $response = $handler->handle($request);

        return $this->withCorsHeaders($this->withStructuredAuthErrorBody($response, $streamFactory), $request);
    }

    private function handleAuthorizedRequest(
        ServerRequestInterface $request,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ): ResponseInterface {
        if ($request->getMethod() === 'GET') {
            return $this->handleGetRequest($request, $responseFactory, $streamFactory);
        }

        $sessionId = $this->sessionIdFromRequest($request, $responseFactory, $streamFactory);
        if ($sessionId instanceof ResponseInterface) {
            return $sessionId;
        }

        if ($request->getMethod() === 'DELETE' && $sessionId instanceof Uuid && !$this->sessionStore->exists($sessionId)) {
            return $this->sessionNotFoundResponse($responseFactory, $streamFactory);
        }

        $transport = new StreamableHttpTransport(
            request: $request,
            responseFactory: $responseFactory,
            streamFactory: $streamFactory,
        );

        return $this->serverFactory->create($this->sessionStore)->run($transport);
    }

    private function authorizationTokenValidator(): AuthorizationTokenValidatorInterface
    {
        if ($this->tokenValidator instanceof AuthorizationTokenValidatorInterface) {
            return $this->tokenValidator;
        }

        if ($this->sharedToken !== null && $this->sharedToken !== '') {
            return (new HttpAuthFactory())->sharedTokenValidator($this->sharedToken);
        }

        return new class () implements AuthorizationTokenValidatorInterface {
            public function validate(string $accessToken): AuthorizationResult
            {
                return AuthorizationResult::unauthorized('invalid_token', 'No HTTP token validator is configured.');
            }
        };
    }

    private function protectedResourceMetadata(ServerRequestInterface $request): ProtectedResourceMetadata
    {
        if ($this->protectedResourceMetadata instanceof ProtectedResourceMetadata) {
            return $this->protectedResourceMetadata;
        }

        $uri = $request->getUri();
        $authority = $uri->getAuthority() !== '' ? $uri->getAuthority() : '127.0.0.1';

        return (new HttpAuthFactory())->metadata(
            issuer: $uri->getScheme() . '://' . $authority,
            resource: $uri->getScheme() . '://' . $authority . $this->path,
        );
    }

    private function isProtectedResourceMetadataRequest(
        ServerRequestInterface $request,
        ProtectedResourceMetadata $metadata,
    ): bool {
        return $request->getMethod() === 'GET'
            && in_array($request->getUri()->getPath(), $metadata->getMetadataPaths(), true);
    }

    private function handleGetRequest(
        ServerRequestInterface $request,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ): ResponseInterface {
        $sessionId = $request->getHeaderLine(self::SESSION_HEADER);
        if ($sessionId === '') {
            return $responseFactory->createResponse(400)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($streamFactory->createStream($this->encodeError(self::SESSION_HEADER . ' header is required.')));
        }

        try {
            $uuid = Uuid::fromString($sessionId);
        } catch (\Throwable) {
            return $responseFactory->createResponse(400)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($streamFactory->createStream($this->encodeError('Invalid ' . self::SESSION_HEADER . ' header.')));
        }

        if (!$this->sessionStore->exists($uuid)) {
            return $this->sessionNotFoundResponse($responseFactory, $streamFactory);
        }

        return $this->serverFactory->create($this->sessionStore)->run(
            new StreamableHttpGetTransport(
                sessionIdValue: $uuid,
                responseFactory: $responseFactory,
                maxSeconds: $this->sseMaxSeconds,
                pollIntervalMicros: $this->ssePollIntervalMicros,
            ),
        );
    }

    private function sessionIdFromRequest(
        ServerRequestInterface $request,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ): Uuid|ResponseInterface|null {
        $sessionId = $request->getHeaderLine(self::SESSION_HEADER);
        if ($sessionId === '') {
            return null;
        }

        try {
            return Uuid::fromString($sessionId);
        } catch (\Throwable) {
            return $responseFactory->createResponse(400)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($streamFactory->createStream($this->encodeError('Invalid ' . self::SESSION_HEADER . ' header.')));
        }
    }

    private function sessionNotFoundResponse(
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ): ResponseInterface {
        return $responseFactory->createResponse(404)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($streamFactory->createStream($this->encodeError('Session not found or has expired.')));
    }

    private function validateOrigin(
        ServerRequestInterface $request,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ): ?ResponseInterface {
        if ((new HttpOriginPolicy($this->allowedOrigins))->allows($request->getHeaderLine('Origin'))) {
            return null;
        }

        return $responseFactory->createResponse(403)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($streamFactory->createStream($this->encodeError('Origin is not allowed.')));
    }

    private function rejectQueryCredentials(
        ServerRequestInterface $request,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ): ?ResponseInterface {
        parse_str($request->getUri()->getQuery(), $query);
        foreach (['access_token', 'token', 'authorization'] as $name) {
            if (array_key_exists($name, $query)) {
                return $responseFactory->createResponse(400)
                    ->withHeader('Content-Type', 'application/json')
                    ->withHeader('WWW-Authenticate', 'Bearer error="invalid_request"')
                    ->withBody($streamFactory->createStream($this->encodeAuthError('invalid_request', 'Query-string credentials are not allowed.')));
            }
        }

        return null;
    }

    private function withStructuredAuthErrorBody(
        ResponseInterface $response,
        StreamFactoryInterface $streamFactory,
    ): ResponseInterface {
        if (!in_array($response->getStatusCode(), [400, 401, 403], true)) {
            return $response;
        }

        if ((string) $response->getBody() !== '') {
            return $response;
        }

        $authenticate = $response->getHeaderLine('WWW-Authenticate');
        $error = str_contains($authenticate, 'insufficient_scope') ? 'insufficient_scope' : 'invalid_token';
        if ($response->getStatusCode() === 400) {
            $error = 'invalid_request';
        } elseif ($response->getStatusCode() === 401 && !str_contains($authenticate, 'error=')) {
            $error = 'authorization_required';
        }

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withBody($streamFactory->createStream($this->encodeAuthError($error, $this->authErrorMessage($error))));
    }

    private function withCorsHeaders(ResponseInterface $response, ServerRequestInterface $request): ResponseInterface
    {
        $origin = trim($request->getHeaderLine('Origin'));
        $headers = [
            'Access-Control-Allow-Origin' => $origin !== '' ? $origin : '*',
            'Access-Control-Allow-Methods' => 'GET, POST, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Accept,Authorization,Content-Type,Last-Event-ID,Mcp-Protocol-Version,' . self::SESSION_HEADER,
            'Access-Control-Expose-Headers' => self::SESSION_HEADER,
        ];

        foreach ($headers as $name => $value) {
            if (!$response->hasHeader($name)) {
                $response = $response->withHeader($name, $value);
            }
        }

        if ($origin !== '') {
            $response = $this->withVaryOrigin($response);
        }

        return $response;
    }

    private function withVaryOrigin(ResponseInterface $response): ResponseInterface
    {
        $vary = $response->getHeaderLine('Vary');
        if ($vary === '') {
            return $response->withHeader('Vary', 'Origin');
        }

        $values = array_map(static fn (string $value): string => strtolower(trim($value)), explode(',', $vary));
        if (!in_array('origin', $values, true)) {
            return $response->withHeader('Vary', $vary . ', Origin');
        }

        return $response;
    }

    private function encodeAuthError(string $error, string $message): string
    {
        return json_encode([
            'jsonrpc' => MessageInterface::JSONRPC_VERSION,
            'error' => [
                'code' => -32001,
                'message' => $message,
                'data' => [
                    'error' => $error,
                ],
            ],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    private function authErrorMessage(string $error): string
    {
        return match ($error) {
            'authorization_required' => 'Bearer authorization is required.',
            'invalid_request' => 'Malformed authorization request.',
            'insufficient_scope' => 'insufficient_scope',
            default => 'Invalid bearer token.',
        };
    }

    private function encodeError(string $message): string
    {
        return json_encode(Error::forInvalidRequest($message), JSON_THROW_ON_ERROR);
    }
}
