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

        if ($request->getMethod() === 'GET') {
            return $this->handleGetRequest($request, $responseFactory, $streamFactory);
        }

        $sessionId = $request->getHeaderLine(self::SESSION_HEADER);
        if ($sessionId !== '') {
            try {
                Uuid::fromString($sessionId);
            } catch (\Throwable) {
                return $responseFactory->createResponse(400)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($streamFactory->createStream($this->encodeError('Invalid ' . self::SESSION_HEADER . ' header.')));
            }
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
            return $responseFactory->createResponse(404)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($streamFactory->createStream($this->encodeError('Session not found or has expired.')));
        }

        return $responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')
            ->withHeader(self::SESSION_HEADER, $sessionId)
            ->withBody($streamFactory->createStream(": connected\n\n"));
    }

    private function encodeError(string $message): string
    {
        return json_encode(Error::forInvalidRequest($message), JSON_THROW_ON_ERROR);
    }
}
