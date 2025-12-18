<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Handlers;

use Bnomei\KirbyMcp\Mcp\LoggingState;
use Mcp\Schema\JsonRpc\Request;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Request\SetLogLevelRequest;
use Mcp\Schema\Result\EmptyResult;
use Mcp\Server\Handler\Request\RequestHandlerInterface;
use Mcp\Server\Session\SessionInterface;

/**
 * @implements RequestHandlerInterface<EmptyResult>
 */
final class SetLogLevelHandler implements RequestHandlerInterface
{
    public function supports(Request $request): bool
    {
        return $request instanceof SetLogLevelRequest;
    }

    /**
     * @return Response<EmptyResult>
     */
    public function handle(Request $request, SessionInterface $session): Response
    {
        \assert($request instanceof SetLogLevelRequest);

        LoggingState::setLevel($request->level);
        $session->set('kirby_mcp.logging.level', $request->level->value);

        return new Response($request->getId(), new EmptyResult());
    }
}
