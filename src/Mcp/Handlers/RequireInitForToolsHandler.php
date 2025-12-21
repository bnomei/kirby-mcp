<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Handlers;

use Bnomei\KirbyMcp\Mcp\SessionState;
use Bnomei\KirbyMcp\Mcp\Tools\SessionTools;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Schema\JsonRpc\Request;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Handler\Request\RequestHandlerInterface;
use Mcp\Server\Session\SessionInterface;

/**
 * @implements RequestHandlerInterface<CallToolResult>
 */
final class RequireInitForToolsHandler implements RequestHandlerInterface
{
    /**
     * @var RequestHandlerInterface<CallToolResult>
     */
    private RequestHandlerInterface $callToolHandler;

    /**
     * @param RequestHandlerInterface<CallToolResult> $callToolHandler
     */
    public function __construct(RequestHandlerInterface $callToolHandler)
    {
        $this->callToolHandler = $callToolHandler;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof CallToolRequest;
    }

    /**
     * @return Response<CallToolResult>|Error
     */
    public function handle(Request $request, SessionInterface $session): Response|Error
    {
        \assert($request instanceof CallToolRequest);

        if ($request->name !== 'kirby_init' && !SessionState::initCalled($session)) {
            $message = SessionTools::initRequiredMessage($request->name);

            return new Response($request->getId(), CallToolResult::error([
                new TextContent($message),
            ]));
        }

        return $this->callToolHandler->handle($request, $session);
    }
}
