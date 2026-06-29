<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Tools\RuntimeTools;

function runtimeToolsContentAsObject(array $payload): array
{
    $method = new ReflectionMethod(RuntimeTools::class, 'contentAsObject');

    return $method->invoke(null, $payload);
}

it('forces empty content to a JSON object to match the output schema', function (): void {
    $result = runtimeToolsContentAsObject(['ok' => true, 'content' => []]);

    expect($result['content'])->toBeInstanceOf(stdClass::class);

    $encoded = json_encode($result, JSON_THROW_ON_ERROR);
    expect($encoded)->toContain('"content":{}');
    expect($encoded)->not()->toContain('"content":[]');
});

it('leaves non-empty content arrays untouched (they encode as objects)', function (): void {
    $result = runtimeToolsContentAsObject(['ok' => true, 'content' => ['title' => 'Home']]);

    expect($result['content'])->toBe(['title' => 'Home']);
    expect(json_encode($result, JSON_THROW_ON_ERROR))->toContain('"content":{"title":"Home"}');
});

it('leaves payloads without a content key untouched', function (): void {
    $result = runtimeToolsContentAsObject(['ok' => false, 'message' => 'nope']);

    expect($result)->toBe(['ok' => false, 'message' => 'nope']);
});
