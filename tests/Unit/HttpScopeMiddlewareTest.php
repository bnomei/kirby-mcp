<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Http\HttpAuthScopes;
use Bnomei\KirbyMcp\Mcp\Http\HttpScopeMiddleware;
use Bnomei\KirbyMcp\Mcp\Http\HttpScopePolicy;
use GuzzleHttp\Psr7\HttpFactory;
use Mcp\Server\Authorization\AccessToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

function httpScopeMiddleware(): HttpScopeMiddleware
{
    $factory = new HttpFactory();

    return new HttpScopeMiddleware(new HttpScopePolicy(), $factory, $factory);
}

function httpScopeOkHandler(): RequestHandlerInterface
{
    return new class () implements RequestHandlerInterface {
        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            return (new HttpFactory())->createResponse(200);
        }
    };
}

it('does not trust legacy OAuth attributes instead of the validated access token', function (): void {
    $factory = new HttpFactory();
    $request = $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
        ->withAttribute('oauth.scopes', HttpAuthScopes::all())
        ->withBody($factory->createStream('null'));

    expect(httpScopeMiddleware()->process($request, httpScopeOkHandler())->getStatusCode())->toBe(403);
});

it('requires execute and explicit write scopes for custom command calls', function (AccessToken $token, bool $allowWrite, int $status): void {
    $factory = new HttpFactory();
    $request = $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
        ->withAttribute(AccessToken::class, $token)
        ->withBody($factory->createStream(json_encode([
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call',
            'params' => ['name' => 'custom_report', 'arguments' => ['allowWrite' => $allowWrite]],
        ], JSON_THROW_ON_ERROR)));
    expect(httpScopeMiddleware()->process($request, httpScopeOkHandler())->getStatusCode())->toBe($status);
})->with([
    'read is insufficient' => [new AccessToken([HttpAuthScopes::READ]), false, 403],
    'execute permits read command' => [new AccessToken([HttpAuthScopes::EXECUTE]), false, 200],
    'execute alone cannot write' => [new AccessToken([HttpAuthScopes::EXECUTE]), true, 403],
    'write alone cannot execute' => [new AccessToken([HttpAuthScopes::WRITE]), true, 403],
    'execute plus write' => [new AccessToken([HttpAuthScopes::EXECUTE, HttpAuthScopes::WRITE]), true, 200],
]);

it('treats a valid JSON-scalar POST body as the read floor without erroring', function (string $body): void {
    $factory = new HttpFactory();
    $request = $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
        ->withAttribute(\Mcp\Server\Authorization\AccessToken::class, new \Mcp\Server\Authorization\AccessToken([HttpAuthScopes::READ]))
        ->withBody($factory->createStream($body));

    $response = httpScopeMiddleware()->process($request, httpScopeOkHandler());

    expect($response->getStatusCode())->toBe(200);
})->with([
    'null literal' => ['null'],
    'integer literal' => ['5'],
    'boolean literal' => ['true'],
    'string literal' => ['"x"'],
]);
