<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Handlers;

use Bnomei\KirbyMcp\Mcp\Activity;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Schema\JsonRpc\Request;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\Result\ReadResourceResult;
use Mcp\Server\Handler\Request\RequestHandlerInterface;
use Mcp\Server\Session\SessionInterface;

/**
 * @template T
 * @implements RequestHandlerInterface<T>
 */
final readonly class ActivityHandler implements RequestHandlerInterface
{
    /** @param RequestHandlerInterface<T> $handler */
    public function __construct(private RequestHandlerInterface $handler, private Activity $activity)
    {
    }

    public function supports(Request $request): bool
    {
        return $this->handler->supports($request);
    }

    /** @return Response<T>|Error */
    public function handle(Request $request, SessionInterface $session): Response|Error
    {
        $response = $this->handler->handle($request, $session);
        if ($response instanceof Response && (
            $response->result instanceof ReadResourceResult ||
            ($response->result instanceof CallToolResult && !$response->result->isError)
        )) {
            $this->activity->record();
        }

        return $response;
    }
}
