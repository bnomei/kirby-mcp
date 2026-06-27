<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Http;

use Mcp\Schema\JsonRpc\MessageInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class HttpScopeMiddleware implements MiddlewareInterface
{
    public function __construct(
        private HttpScopePolicy $scopePolicy,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $required = strtoupper($request->getMethod()) === 'POST'
            ? $this->requiredPostScopes($request)
            : $this->scopePolicy->methodScopes($request->getMethod());

        if ($required === [] || $this->hasScopes($request, $required)) {
            return $handler->handle($request);
        }

        return $this->insufficientScopeResponse($required);
    }

    /**
     * @return list<string>
     */
    private function requiredPostScopes(ServerRequestInterface $request): array
    {
        $body = $request->getBody()->__toString();
        $request->getBody()->rewind();
        if (trim($body) === '') {
            return [HttpAuthScopes::READ];
        }

        try {
            $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [HttpAuthScopes::READ];
        }

        if (!is_array($payload)) {
            return [HttpAuthScopes::READ];
        }

        $messages = array_is_list($payload) ? $payload : [$payload];
        $required = [];
        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }

            $method = $message['method'] ?? null;
            if (!is_string($method)) {
                continue;
            }

            $params = $message['params'] ?? null;
            if ($params instanceof \stdClass) {
                $params = (array) $params;
            }

            foreach ($this->scopePolicy->requiredScopes($method, is_array($params) ? $params : null) as $scope) {
                $required[] = $scope;
            }
        }

        return array_values(array_unique($required));
    }

    /**
     * @param list<string> $requiredScopes
     */
    private function hasScopes(ServerRequestInterface $request, array $requiredScopes): bool
    {
        $tokenScopes = $request->getAttribute('oauth.scopes', []);
        if (!is_array($tokenScopes)) {
            $tokenScopes = [];
        }

        foreach ($requiredScopes as $requiredScope) {
            if (!in_array($requiredScope, $tokenScopes, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<string> $requiredScopes
     */
    private function insufficientScopeResponse(array $requiredScopes): ResponseInterface
    {
        return $this->responseFactory->createResponse(403)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('WWW-Authenticate', 'Bearer error="insufficient_scope", scope="' . implode(' ', $requiredScopes) . '"')
            ->withBody($this->streamFactory->createStream(json_encode([
                'jsonrpc' => MessageInterface::JSONRPC_VERSION,
                'error' => [
                    'code' => -32003,
                    'message' => 'insufficient_scope',
                    'data' => [
                        'requiredScopes' => $requiredScopes,
                    ],
                ],
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)));
    }
}
