<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Http\HttpAuthScopes;
use Bnomei\KirbyMcp\Mcp\Http\HttpScopeMiddleware;
use Bnomei\KirbyMcp\Mcp\Http\HttpScopePolicy;
use GuzzleHttp\Psr7\HttpFactory;
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

it('treats a valid JSON-scalar POST body as the read floor without erroring', function (string $body): void {
    $factory = new HttpFactory();
    $request = $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
        ->withAttribute('oauth.scopes', [HttpAuthScopes::READ])
        ->withBody($factory->createStream($body));

    $response = httpScopeMiddleware()->process($request, httpScopeOkHandler());

    expect($response->getStatusCode())->toBe(200);
})->with([
    'null literal' => ['null'],
    'integer literal' => ['5'],
    'boolean literal' => ['true'],
    'string literal' => ['"x"'],
]);
