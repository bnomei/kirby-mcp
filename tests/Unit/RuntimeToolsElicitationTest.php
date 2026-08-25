<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Tools\RuntimeTools;
use Mcp\Schema\Elicitation\ElicitationSchema;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Request\ElicitRequest;
use Mcp\Server\RequestContext;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;

it('uses a titled enum schema for confirmation elicitation', function (): void {
    $session = new Session(new InMemorySessionStore(60));
    $session->set('client_capabilities', ['elicitation' => []]);

    $context = new RequestContext(
        $session,
        (new CallToolRequest('kirby_update_page_content', []))->withId('test'),
    );

    $tools = new RuntimeTools();
    $method = new ReflectionMethod($tools, 'requestElicitedConfirm');

    $fiber = new Fiber(static fn (): mixed => $method->invoke($tools, $context, 'Execute this write?'));
    $suspended = $fiber->start();

    expect($suspended)->toBeArray();
    expect($suspended['type'] ?? null)->toBe('request');
    expect($suspended['input_key'] ?? null)->toMatch('/^kirby-confirm-[a-f0-9]{64}$/');

    $request = $suspended['request'] ?? null;
    expect($request)->toBeInstanceOf(ElicitRequest::class);
    if (!$request instanceof ElicitRequest) {
        throw new RuntimeException('Expected an elicitation request.');
    }

    expect($request->requestedSchema)->toBeInstanceOf(ElicitationSchema::class);
    if (!$request->requestedSchema instanceof ElicitationSchema) {
        throw new RuntimeException('Expected a form elicitation schema.');
    }

    $schema = $request->requestedSchema->jsonSerialize();

    expect($schema['properties']['confirm'] ?? null)->toMatchArray([
        'type' => 'string',
        'title' => 'Confirm execution',
        'description' => 'Choose whether to execute now or keep the dry-run response.',
        'oneOf' => [
            ['const' => 'execute', 'title' => 'Execute now'],
            ['const' => 'preview', 'title' => 'Keep dry-run preview'],
        ],
        'default' => 'preview',
    ]);
    expect($schema['required'] ?? null)->toBe(['confirm']);

    $fiber->resume(new Response(1, [
        'action' => 'accept',
        'content' => ['confirm' => 'execute'],
    ]));

    expect($fiber->getReturn())->toBeTrue();
});

it('binds stateless confirmation answers to every security-relevant operation input', function (): void {
    $session = new Session(new InMemorySessionStore(60));
    $session->set('client_capabilities', ['elicitation' => []]);
    $context = new RequestContext(
        $session,
        (new CallToolRequest('kirby_eval', []))->withId('test'),
    );
    $tools = new RuntimeTools();
    $method = new ReflectionMethod($tools, 'requestElicitedConfirm');

    $keyFor = static function (array $operation) use ($method, $tools, $context): string {
        $fiber = new Fiber(static fn (): mixed => $method->invoke($tools, $context, 'Execute?', $operation));
        $suspended = $fiber->start();

        expect($suspended)->toBeArray();
        $key = $suspended['input_key'] ?? null;
        expect($key)->toBeString();

        return $key;
    };

    $first = ['tool' => 'kirby_eval', 'code' => 'return 1;', 'timeoutSeconds' => 30, 'debug' => false];

    expect($keyFor($first))->toBe($keyFor($first))
        ->and($keyFor([...$first, 'code' => 'return 2;']))->not()->toBe($keyFor($first))
        ->and($keyFor([...$first, 'timeoutSeconds' => 31]))->not()->toBe($keyFor($first));
});

it('keeps legacy boolean confirmation responses working defensively', function (): void {
    $session = new Session(new InMemorySessionStore(60));
    $session->set('client_capabilities', ['elicitation' => []]);

    $context = new RequestContext(
        $session,
        (new CallToolRequest('kirby_update_page_content', []))->withId('test'),
    );

    $tools = new RuntimeTools();
    $method = new ReflectionMethod($tools, 'requestElicitedConfirm');

    $fiber = new Fiber(static fn (): mixed => $method->invoke($tools, $context, 'Execute this write?'));
    $fiber->start();
    $fiber->resume(new Response(1, [
        'action' => 'accept',
        'content' => ['confirm' => true],
    ]));

    expect($fiber->getReturn())->toBeTrue();
});
