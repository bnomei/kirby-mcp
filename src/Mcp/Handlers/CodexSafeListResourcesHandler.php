<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Handlers;

use Mcp\Capability\RegistryInterface;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Schema\JsonRpc\Request;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Request\ListResourcesRequest;
use Mcp\Schema\ResourceDefinition;
use Mcp\Schema\Result\ListResourcesResult;
use Mcp\Server\Handler\Request\RequestHandlerInterface;
use Mcp\Server\Session\SessionInterface;

/**
 * @implements RequestHandlerInterface<ListResourcesResult>
 */
final class CodexSafeListResourcesHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly RegistryInterface $registry,
        private readonly int $pageSize = 50,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request instanceof ListResourcesRequest;
    }

    /**
     * @return Response<ListResourcesResult>|Error
     */
    public function handle(Request $request, SessionInterface $session): Response|Error
    {
        \assert($request instanceof ListResourcesRequest);

        $page = $this->registry->getResources($this->pageSize, $request->cursor);
        $resources = [];
        foreach ($page->references as $resource) {
            if ($resource instanceof ResourceDefinition) {
                $resources[] = self::plainDescriptor($resource);
            }
        }

        return new Response(
            $request->getId(),
            new ListResourcesResult(
                $resources,
                $page->nextCursor,
            ),
        );
    }

    private static function plainDescriptor(ResourceDefinition $resource): ResourceDefinition
    {
        return new ResourceDefinition(
            uri: $resource->uri,
            name: $resource->name,
            title: $resource->title,
            description: $resource->description,
            mimeType: $resource->mimeType,
        );
    }
}
