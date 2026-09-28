<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Handlers;

use Mcp\Schema\Enum\CacheScope;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Schema\JsonRpc\Request;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Request\ReadResourceRequest;
use Mcp\Schema\Result\ReadResourceResult;
use Mcp\Server\Handler\Request\ReadResourceHandler;
use Mcp\Server\Handler\Request\RequestHandlerInterface;
use Mcp\Server\RequestContext;
use Mcp\Server\Session\SessionInterface;

/**
 * Adds modern cache hints only to immutable resources bundled with this package.
 *
 * @implements RequestHandlerInterface<ReadResourceResult>
 */
final class BundledReadResourceHandler implements RequestHandlerInterface
{
    public const TTL_MS = 3_600_000;

    public function __construct(
        private readonly ReadResourceHandler $handler,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request instanceof ReadResourceRequest && $this->isBundledResource($request->uri);
    }

    public function handle(Request $request, SessionInterface $session): Response|Error
    {
        $response = $this->handler->handle($request, $session);
        if (!$response instanceof Response || !$response->result instanceof ReadResourceResult) {
            return $response;
        }

        if (!(new RequestContext($session, $request))->getProtocolVersion()->isModern()) {
            return $response;
        }

        return new Response($response->id, new ReadResourceResult(
            contents: $response->result->contents,
            ttlMs: self::TTL_MS,
            cacheScope: CacheScope::Public,
        ));
    }

    private function isBundledResource(string $uri): bool
    {
        if (in_array($uri, [
            'kirby://blueprints/update-schema',
            'kirby://extensions',
            'kirby://fields',
            'kirby://fields/update-schema',
            'kirby://glossary',
            'kirby://hooks',
            'kirby://kb',
            'kirby://sections',
        ], true)) {
            return true;
        }

        return preg_match('#^kirby://(?:kb/[^/].*|glossary/[a-z0-9-]+|field/[a-z0-9-]+/update-schema|blueprint/[a-z0-9-]+/update-schema)$#', $uri) === 1;
    }
}
