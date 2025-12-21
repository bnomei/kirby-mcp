<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Handlers\RequireInitForToolsHandler;
use Bnomei\KirbyMcp\Mcp\SessionState;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Schema\JsonRpc\Request;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Handler\Request\RequestHandlerInterface;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;
use Mcp\Server\Session\SessionInterface;

/**
 * @implements RequestHandlerInterface<CallToolResult>
 */
final class FakeCallToolHandler implements RequestHandlerInterface
{
    public int $calls = 0;

    public function supports(Request $request): bool
    {
        return $request instanceof CallToolRequest;
    }

    public function handle(Request $request, SessionInterface $session): Response|Error
    {
        $this->calls++;

        return new Response($request->getId(), CallToolResult::success([
            new TextContent('ok'),
        ]));
    }
}

it('blocks tool calls until kirby_init is called', function (): void {
    $callToolHandler = new FakeCallToolHandler();
    $handler = new RequireInitForToolsHandler($callToolHandler);
    $request = (new CallToolRequest('kirby_search', ['query' => 'config options']))->withId(1);
    $session = new Session(new InMemorySessionStore(60));

    expect($handler->supports($request))->toBeTrue();

    $response = $handler->handle($request, $session);

    expect($response->result)->toBeInstanceOf(CallToolResult::class);
    expect($response->result->isError)->toBeTrue();

    $content = $response->result->content;
    expect($content)->toHaveCount(1);
    expect($content[0])->toBeInstanceOf(TextContent::class);
    expect((string) $content[0]->text)->toContain('kirby_init');
    expect((string) $content[0]->text)->toContain('kirby_search');
    expect($callToolHandler->calls)->toBe(0);
});

it('does not block kirby_init tool calls', function (): void {
    $callToolHandler = new FakeCallToolHandler();
    $handler = new RequireInitForToolsHandler($callToolHandler);
    $request = (new CallToolRequest('kirby_init', []))->withId(1);
    $session = new Session(new InMemorySessionStore(60));

    expect($handler->supports($request))->toBeTrue();

    $response = $handler->handle($request, $session);

    expect($response->result)->toBeInstanceOf(CallToolResult::class);
    expect($response->result->isError)->toBeFalse();
    expect($callToolHandler->calls)->toBe(1);
});

it('does not block tool calls after kirby_init', function (): void {
    $session = new Session(new InMemorySessionStore(60));
    SessionState::markInitCalled($session);

    $callToolHandler = new FakeCallToolHandler();
    $handler = new RequireInitForToolsHandler($callToolHandler);
    $request = (new CallToolRequest('kirby_search', ['query' => 'config options']))->withId(1);

    expect($handler->supports($request))->toBeTrue();

    $response = $handler->handle($request, $session);

    expect($response->result)->toBeInstanceOf(CallToolResult::class);
    expect($response->result->isError)->toBeFalse();
    expect($callToolHandler->calls)->toBe(1);
});

it('keeps init state scoped to the session', function (): void {
    $sessionA = new Session(new InMemorySessionStore(60));
    $sessionB = new Session(new InMemorySessionStore(60));

    SessionState::markInitCalled($sessionA);

    $callToolHandler = new FakeCallToolHandler();
    $handler = new RequireInitForToolsHandler($callToolHandler);
    $request = (new CallToolRequest('kirby_search', ['query' => 'config options']))->withId(1);

    $responseA = $handler->handle($request, $sessionA);
    $responseB = $handler->handle($request, $sessionB);

    expect($responseA->result)->toBeInstanceOf(CallToolResult::class);
    expect($responseA->result->isError)->toBeFalse();
    expect($responseB->result)->toBeInstanceOf(CallToolResult::class);
    expect($responseB->result->isError)->toBeTrue();
    expect($callToolHandler->calls)->toBe(1);
});
