<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use GuzzleHttp\Psr7\HttpFactory;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Server\Session\SessionStoreInterface;
use Mcp\Server\Transport\StreamableHttpTransport;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\Uid\Uuid;

final class HttpMcpTracer
{
    private const SESSION_HEADER = 'Mcp-Session-Id';

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
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $responseFactory = $this->responseFactory ?? new HttpFactory();
        $streamFactory = $this->streamFactory ?? new HttpFactory();

        if ($request->getUri()->getPath() !== $this->path) {
            return $responseFactory->createResponse(404)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($streamFactory->createStream($this->encodeError('MCP endpoint not found.')));
        }

        $originResponse = $this->validateOrigin($request, $responseFactory, $streamFactory);
        if ($originResponse instanceof ResponseInterface) {
            return $originResponse;
        }

        if ($request->getMethod() === 'OPTIONS') {
            return $this->serverFactory->create($this->sessionStore)->run(new StreamableHttpTransport(
                request: $request,
                responseFactory: $responseFactory,
                streamFactory: $streamFactory,
            ));
        }

        $authResponse = $this->validateSharedToken($request, $responseFactory, $streamFactory);
        if ($authResponse instanceof ResponseInterface) {
            return $authResponse;
        }

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
        $origin = trim($request->getHeaderLine('Origin'));
        if ($origin === '' || $this->allowedOrigins === []) {
            return null;
        }

        if (in_array($origin, $this->allowedOrigins, true)) {
            return null;
        }

        return $responseFactory->createResponse(403)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($streamFactory->createStream($this->encodeError('Origin is not allowed.')));
    }

    private function validateSharedToken(
        ServerRequestInterface $request,
        ResponseFactoryInterface $responseFactory,
        StreamFactoryInterface $streamFactory,
    ): ?ResponseInterface {
        if ($this->sharedToken === null || $this->sharedToken === '') {
            return null;
        }

        $authorization = trim($request->getHeaderLine('Authorization'));
        if (!preg_match('/^Bearer\s+(.+)$/', $authorization, $matches)) {
            return $responseFactory->createResponse(401)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('WWW-Authenticate', 'Bearer')
                ->withBody($streamFactory->createStream($this->encodeError('Bearer authorization is required.')));
        }

        if (!hash_equals($this->sharedToken, $matches[1])) {
            return $responseFactory->createResponse(401)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('WWW-Authenticate', 'Bearer error="invalid_token"')
                ->withBody($streamFactory->createStream($this->encodeError('Invalid bearer token.')));
        }

        return null;
    }

    private function encodeError(string $message): string
    {
        return json_encode(Error::forInvalidRequest($message), JSON_THROW_ON_ERROR);
    }
}
