<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Permissions;
use Bnomei\KirbyMcp\Mcp\Resources\MetaResources;
use Bnomei\KirbyMcp\Mcp\Tools\MetaTools;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;

it('maps every registered tool and resource exactly once', function (): void {
    $tools = [];
    $resources = [];
    foreach (['Tools', 'Resources'] as $directory) {
        foreach (glob(dirname(__DIR__, 2) . '/src/Mcp/' . $directory . '/*.php') ?: [] as $file) {
            $class = 'Bnomei\\KirbyMcp\\Mcp\\' . $directory . '\\' . basename($file, '.php');
            if (!class_exists($class)) {
                throw new RuntimeException('Cannot inspect ' . $class);
            }
            foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                foreach ($method->getAttributes(McpTool::class) as $attribute) {
                    $tools[] = $attribute->newInstance()->name;
                }
                foreach ($method->getAttributes(McpResource::class) as $attribute) {
                    $resources[] = $attribute->newInstance()->uri;
                }
                foreach ($method->getAttributes(McpResourceTemplate::class) as $attribute) {
                    $resources[] = $attribute->newInstance()->uriTemplate;
                }
            }
        }
    }
    expect($tools)->toEqualCanonicalizing(array_keys(Permissions::TOOLS))
        ->and($resources)->toEqualCanonicalizing(array_keys(Permissions::RESOURCES));
});

it('requires access and every parent and leaf without implicit grants', function (): void {
    $values = ['access' => true, 'content' => true, 'content.pages' => true, 'content.pages.read' => true];
    $policy = new Permissions($values);
    expect($policy->tool('kirby_read_page_content'))->toBeTrue()
        ->and($policy->tool('kirby_update_page_content'))->toBeFalse()
        ->and($policy->tool('unmapped'))->toBeFalse();
    foreach (array_keys($values) as $key) {
        expect((new Permissions(array_replace($values, [$key => false])))->tool('kirby_read_page_content'))->toBeFalse();
    }
    expect(array_unique(array_values(Permissions::defaults())))->toBe([false]);
});

it('filters indexes and suggestions while leaving local discovery unchanged', function (): void {
    $policy = new Permissions(['access' => true, 'content' => true, 'content.pages' => true, 'content.pages.read' => true]);
    $expected = ['kirby_read_page_content', 'kirby://page/content/{encodedIdOrUuid}'];
    $index = (new MetaResources(permissions: $policy))->toolIndex();
    $suggestions = (new MetaTools(permissions: $policy))->suggestTools(query: 'eval', limit: 25);
    expect(array_column($index, 'name'))->toEqualCanonicalizing($expected)
        ->and(array_column($suggestions['suggestions'], 'name'))->toEqualCanonicalizing($expected)
        ->and(array_column((new MetaResources())->toolIndex(), 'name'))->toContain('kirby_eval');
});

it('matches concrete resource URIs without crossing template boundaries', function (): void {
    $policy = new Permissions(['access' => true, 'content' => true, 'content.pages' => true, 'content.pages.read' => true,
        'reference' => true, 'reference.schemas' => true]);
    expect($policy->resource('kirby://page/content/notes%2Fhello'))->toBeTrue()
        ->and($policy->resource('kirby://page/content/{encodedIdOrUuid}'))->toBeTrue()
        ->and($policy->resource('kirby://page/content/notes/hello'))->toBeFalse()
        ->and($policy->resource('kirby://page/content/'))->toBeFalse()
        ->and($policy->resource('kirby://file/content/notes%2Fhello'))->toBeFalse()
        ->and($policy->resource('kirby://blueprint/page/update-schema'))->toBeTrue()
        ->and($policy->resource('kirby://blueprint/pages%2Farticle'))->toBeFalse();
});

it('checks calls reads completions and subscription requests independently', function (): void {
    $policy = new Permissions(['access' => true, 'content' => true, 'content.pages' => true, 'content.pages.read' => true]);
    foreach (['resources/read', 'resources/subscribe', 'resources/unsubscribe'] as $method) {
        expect($policy->request(['method' => $method, 'params' => ['uri' => 'kirby://page/content/home']]))->toBeTrue()
            ->and($policy->request(['method' => $method, 'params' => ['uri' => 'kirby://site/content']]))->toBeFalse();
    }
    expect($policy->request(['method' => 'tools/call', 'params' => ['name' => 'kirby_eval']]))->toBeFalse()
        ->and($policy->request(['method' => 'completion/complete', 'params' => ['ref' => ['type' => 'ref/resource', 'uri' => 'kirby://page/content/{encodedIdOrUuid}']]]))->toBeTrue()
        ->and($policy->request(['method' => 'completion/complete', 'params' => ['ref' => ['type' => 'ref/resource', 'uri' => 'kirby://config/{option}']]]))->toBeFalse()
        ->and($policy->request(['method' => 'subscriptions/listen', 'params' => ['notifications' => ['resourceSubscriptions' => ['kirby://page/content/home', 'kirby://site/content']]]]))->toBeFalse()
        ->and($policy->request(['method' => 'subscriptions/listen', 'params' => ['notifications' => ['resourceSubscriptions' => ['kirby://page/content/home']]]]))->toBeTrue();
});
