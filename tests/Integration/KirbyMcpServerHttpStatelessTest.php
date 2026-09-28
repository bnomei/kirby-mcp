<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Http\HttpAuthScopes;
use Bnomei\KirbyMcp\Mcp\Http\SharedTokenValidator;
use Bnomei\KirbyMcp\Mcp\HttpMcpHandler;
use Bnomei\KirbyMcp\Mcp\ServerFactory;
use Bnomei\KirbyMcp\Mcp\Subscription\FileNotificationBus;
use Bnomei\KirbyMcp\Mcp\Tools\RuntimeTools;
use GuzzleHttp\Psr7\HttpFactory;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Schema\Notification\ResourceUpdatedNotification;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Session\FileSessionStore;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

function kirbyMcpStatelessRoot(): string
{
    $root = sys_get_temp_dir() . '/kirby-mcp-stateless-' . bin2hex(random_bytes(6));
    mkdir($root, 0700);

    return $root;
}

function removeKirbyMcpStatelessRoot(string $root): void
{
    if (!is_dir($root)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $item) {
        $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($root);
}

/**
 * @param array{commandsRoot:string, installed:array<int, string>} $install
 */
function removeKirbyMcpStatelessRuntime(array $install): void
{
    $commandsRoot = rtrim($install['commandsRoot'], DIRECTORY_SEPARATOR);
    $directories = [];
    foreach ($install['installed'] as $relativePath) {
        $path = $commandsRoot . DIRECTORY_SEPARATOR . $relativePath;
        if (is_file($path)) {
            @unlink($path);
        }

        $directory = dirname($path);
        while (str_starts_with($directory, $commandsRoot)) {
            $directories[$directory] = true;
            if ($directory === $commandsRoot) {
                break;
            }
            $directory = dirname($directory);
        }
    }

    uksort($directories, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));
    foreach (array_keys($directories) as $directory) {
        $entries = is_dir($directory) ? scandir($directory) : false;
        if ($entries !== false && array_diff($entries, ['.', '..']) === []) {
            rmdir($directory);
        }
    }
}

function kirbyMcpStatelessHandler(
    string $root,
    ?SharedTokenValidator $validator = null,
    int $sseMaxSeconds = 1,
): HttpMcpHandler {
    return new HttpMcpHandler(
        serverFactory: new ServerFactory(),
        sessionStore: new FileSessionStore($root . '/http-sessions'),
        tokenValidator: $validator ?? new SharedTokenValidator('local-secret'),
        projectRoot: $root,
        sseMaxSeconds: $sseMaxSeconds,
    );
}

/**
 * @param array<string, mixed> $params
 * @param array<string, mixed>|object $capabilities
 */
function kirbyMcpStatelessRequest(
    HttpFactory $factory,
    string $method,
    int $id,
    array $params = [],
    ?string $name = null,
    array|object $capabilities = new stdClass(),
): ServerRequestInterface {
    $params['_meta'] = [
        'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
        'io.modelcontextprotocol/clientCapabilities' => $capabilities,
        'io.modelcontextprotocol/clientInfo' => ['name' => 'tests', 'version' => 'dev'],
    ];
    $body = json_encode([
        'jsonrpc' => '2.0',
        'id' => $id,
        'method' => $method,
        'params' => $params,
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

    $request = $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
        ->withHeader('Authorization', 'Bearer local-secret')
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('MCP-Protocol-Version', '2026-07-28')
        ->withHeader('Mcp-Method', $method)
        ->withBody($factory->createStream($body));

    return $name === null ? $request : $request->withHeader('Mcp-Name', $name);
}

/** @return array<string, mixed> */
function kirbyMcpStatelessDecode(ResponseInterface $response): array
{
    $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    expect($payload)->toBeArray();

    return $payload;
}

it('serves discover and tools without a session while enforcing modern headers and scopes', function (): void {
    $root = kirbyMcpStatelessRoot();
    $factory = new HttpFactory();
    $handler = kirbyMcpStatelessHandler(
        $root,
        new SharedTokenValidator('local-secret', [HttpAuthScopes::READ]),
    );

    try {
        $discover = $handler->handle(kirbyMcpStatelessRequest($factory, 'server/discover', 1));
        $discoverPayload = kirbyMcpStatelessDecode($discover);
        expect($discover->getStatusCode())->toBe(200)
            ->and($discover->getHeaderLine('Mcp-Session-Id'))->toBe('')
            ->and($discoverPayload['result']['supportedVersions'] ?? null)->toBe(['2026-07-28'])
            ->and($discoverPayload['result']['capabilities']['resources']['subscribe'] ?? null)->toBeTrue()
            ->and($discoverPayload['result']['ttlMs'] ?? null)->toBe(0)
            ->and($discoverPayload['result']['cacheScope'] ?? null)->toBe('private');

        $toolRequest = kirbyMcpStatelessRequest(
            $factory,
            'tools/call',
            2,
            ['name' => 'kirby_tool_suggest', 'arguments' => ['query' => 'pages section settings options properties', 'limit' => 5]],
            'kirby_tool_suggest',
        );
        $tool = $handler->handle($toolRequest);
        $toolPayload = kirbyMcpStatelessDecode($tool);
        $resourceLinks = array_values(array_filter(
            $toolPayload['result']['content'] ?? [],
            static fn (mixed $content): bool => is_array($content) && ($content['type'] ?? null) === 'resource_link',
        ));
        expect($tool->getStatusCode())->toBe(200)
            ->and($tool->getHeaderLine('Mcp-Session-Id'))->toBe('')
            ->and($toolPayload)->not()->toHaveKey('error')
            ->and($toolPayload['result']['resultType'] ?? null)->toBe('complete')
            ->and(array_column($resourceLinks, 'uri'))->toContain('kirby://section/pages');

        $bundledRead = $handler->handle(kirbyMcpStatelessRequest(
            $factory,
            'resources/read',
            3,
            ['uri' => 'kirby://kb'],
            'kirby://kb',
        ));
        $bundledPayload = kirbyMcpStatelessDecode($bundledRead);
        expect($bundledRead->getStatusCode())->toBe(200)
            ->and($bundledPayload['result']['resultType'] ?? null)->toBe('complete')
            ->and($bundledPayload['result']['ttlMs'] ?? null)->toBe(3600000)
            ->and($bundledPayload['result']['cacheScope'] ?? null)->toBe('public');

        $projectList = $handler->handle(kirbyMcpStatelessRequest($factory, 'tools/list', 4));
        $projectListPayload = kirbyMcpStatelessDecode($projectList);
        expect($projectList->getStatusCode())->toBe(200)
            ->and($projectListPayload['result']['ttlMs'] ?? null)->toBe(0)
            ->and($projectListPayload['result']['cacheScope'] ?? null)->toBe('private');

        $missingMethodHeader = $handler->handle($toolRequest->withoutHeader('Mcp-Method'));
        expect($missingMethodHeader->getStatusCode())->toBe(400)
            ->and(kirbyMcpStatelessDecode($missingMethodHeader)['error']['code'] ?? null)->toBe(Error::HEADER_MISMATCH);

        $wrongName = $handler->handle($toolRequest->withHeader('Mcp-Name', 'kirby_info'));
        expect($wrongName->getStatusCode())->toBe(400)
            ->and(kirbyMcpStatelessDecode($wrongName)['error']['code'] ?? null)->toBe(Error::HEADER_MISMATCH);

        $write = $handler->handle(kirbyMcpStatelessRequest(
            $factory,
            'tools/call',
            5,
            ['name' => 'kirby_update_page_content', 'arguments' => new stdClass()],
            'kirby_update_page_content',
        ));
        expect($write->getStatusCode())->toBe(403)
            ->and($write->getHeaderLine('WWW-Authenticate'))->toContain('insufficient_scope')
            ->and((string) $write->getBody())->toContain(HttpAuthScopes::WRITE);

        $runtimeSubscription = $handler->handle(kirbyMcpStatelessRequest(
            $factory,
            'subscriptions/listen',
            6,
            ['notifications' => ['resourceSubscriptions' => ['kirby://site/content']]],
        )->withHeader('Accept', 'text/event-stream'));
        expect($runtimeSubscription->getStatusCode())->toBe(403)
            ->and($runtimeSubscription->getHeaderLine('WWW-Authenticate'))->toContain('insufficient_scope')
            ->and((string) $runtimeSubscription->getBody())->toContain(HttpAuthScopes::RUNTIME);
    } finally {
        removeKirbyMcpStatelessRoot($root);
    }
});

it('records dispatched activity but not discovery, denied calls, or unknown resources', function (): void {
    $root = kirbyMcpStatelessRoot();
    $previousRoot = getenv('KIRBY_MCP_PROJECT_ROOT');
    putenv('KIRBY_MCP_PROJECT_ROOT=' . $root);
    mkdir($root . '/.kirby-mcp');
    file_put_contents($root . '/.kirby-mcp/mcp.json', '{"activity":{"enabled":true}}');
    $path = $root . '/.kirby-mcp/activity';
    $factory = new HttpFactory();
    $handler = kirbyMcpStatelessHandler($root, new SharedTokenValidator('local-secret', [HttpAuthScopes::READ]));

    try {
        $handler->handle(kirbyMcpStatelessRequest($factory, 'tools/list', 1));
        expect(is_file($path))->toBeFalse();

        $denied = $handler->handle(kirbyMcpStatelessRequest($factory, 'tools/call', 2, [
            'name' => 'kirby_eval', 'arguments' => ['code' => 'return 1;'],
        ], 'kirby_eval'));
        expect($denied->getStatusCode())->toBe(403)
            ->and(is_file($path))->toBeFalse();

        foreach (['kirby://kb', 'kirby://tools'] as $uri) {
            $response = $handler->handle(kirbyMcpStatelessRequest($factory, 'resources/read', 3, ['uri' => $uri], $uri));
            expect(kirbyMcpStatelessDecode($response))->not->toHaveKey('error')
                ->and(is_file($path))->toBeTrue();
            unlink($path);
        }

        $handler->handle(kirbyMcpStatelessRequest($factory, 'resources/read', 4, ['uri' => 'kirby://missing'], 'kirby://missing'));
        expect(is_file($path))->toBeFalse();

        $response = $handler->handle(kirbyMcpStatelessRequest($factory, 'tools/call', 5, [
            'name' => 'kirby_tool_suggest', 'arguments' => ['query' => 'pages', 'limit' => 1],
        ], 'kirby_tool_suggest'));
        expect(kirbyMcpStatelessDecode($response)['result']['isError'] ?? null)->toBeFalse()
            ->and(is_file($path))->toBeTrue();
    } finally {
        putenv($previousRoot === false ? 'KIRBY_MCP_PROJECT_ROOT' : 'KIRBY_MCP_PROJECT_ROOT=' . $previousRoot);
        removeKirbyMcpStatelessRoot($root);
    }
});

it('ignores stale confirmation input and re-asks on a fresh stateless call', function (): void {
    $root = kirbyMcpStatelessRoot();
    $factory = new HttpFactory();
    $handler = kirbyMcpStatelessHandler($root);
    $previousRoot = getenv('KIRBY_MCP_PROJECT_ROOT');
    $previousBinary = getenv('KIRBY_MCP_KIRBY_BIN');
    $previousQuery = getenv('KIRBY_MCP_ENABLE_QUERY');
    putenv('KIRBY_MCP_PROJECT_ROOT=' . cmsPath());
    putenv('KIRBY_MCP_KIRBY_BIN=' . dirname(__DIR__, 2) . '/vendor/bin/kirby');
    putenv('KIRBY_MCP_ENABLE_QUERY=1');
    $capabilities = ['elicitation' => ['form' => new stdClass()]];
    $install = (new RuntimeTools())->runtimeInstall(force: true);
    if ($install instanceof CallToolResult) {
        throw new RuntimeException('Expected direct runtime installation details.');
    }

    try {
        $first = $handler->handle(kirbyMcpStatelessRequest(
            $factory,
            'tools/call',
            1,
            ['name' => 'kirby_query_dot', 'arguments' => ['query' => 'site.title.value']],
            'kirby_query_dot',
            $capabilities,
        ));
        $firstResult = kirbyMcpStatelessDecode($first)['result'] ?? [];
        $firstKey = array_key_first($firstResult['inputRequests'] ?? []);
        expect($firstResult['resultType'] ?? null)->toBe('input_required')
            ->and($firstKey)->toBeString()
            ->and($firstResult)->not()->toHaveKey('requestState');

        $answer = ['action' => 'accept', 'content' => ['confirm' => 'execute']];
        $changed = $handler->handle(kirbyMcpStatelessRequest(
            $factory,
            'tools/call',
            2,
            [
                'name' => 'kirby_query_dot',
                'arguments' => ['query' => 'site.url'],
                'inputResponses' => [$firstKey => $answer],
            ],
            'kirby_query_dot',
            $capabilities,
        ));
        $changedResult = kirbyMcpStatelessDecode($changed)['result'] ?? [];
        expect($changedResult['resultType'] ?? null)->toBe('complete')
            ->and($changedResult['structuredContent']['confirmationStatus'] ?? null)->toBe('stale_input_ignored')
            ->and($changedResult['structuredContent']['retryWithoutInputResponses'] ?? null)->toBeTrue()
            ->and($changedResult['structuredContent']['needsConfirm'] ?? null)->toBeTrue()
            ->and($changedResult['structuredContent']['message'] ?? null)->toContain('different arguments');

        $fresh = $handler->handle(kirbyMcpStatelessRequest(
            $factory,
            'tools/call',
            3,
            [
                'name' => 'kirby_query_dot',
                'arguments' => ['query' => 'site.url'],
            ],
            'kirby_query_dot',
            $capabilities,
        ));
        $freshResult = kirbyMcpStatelessDecode($fresh)['result'] ?? [];
        $freshKey = array_key_first($freshResult['inputRequests'] ?? []);
        expect($freshResult['resultType'] ?? null)->toBe('input_required')
            ->and($freshKey)->toBeString()
            ->and($freshKey)->not()->toBe($firstKey)
            ->and($freshResult)->not()->toHaveKey('requestState');

        $accepted = $handler->handle(kirbyMcpStatelessRequest(
            $factory,
            'tools/call',
            4,
            [
                'name' => 'kirby_query_dot',
                'arguments' => ['query' => 'site.url'],
                'inputResponses' => [$freshKey => $answer],
            ],
            'kirby_query_dot',
            $capabilities,
        ));
        $acceptedResult = kirbyMcpStatelessDecode($accepted)['result'] ?? [];
        expect($acceptedResult['resultType'] ?? null)->toBe('complete')
            ->and($acceptedResult['structuredContent']['confirmedVia'] ?? null)->toBe('elicitation');
    } finally {
        putenv('KIRBY_MCP_PROJECT_ROOT=' . ($previousRoot === false ? '' : $previousRoot));
        putenv('KIRBY_MCP_KIRBY_BIN=' . ($previousBinary === false ? '' : $previousBinary));
        putenv('KIRBY_MCP_ENABLE_QUERY=' . ($previousQuery === false ? '' : $previousQuery));
        removeKirbyMcpStatelessRuntime($install);
        removeKirbyMcpStatelessRoot($root);
    }
});

it('streams resource updates published by a separate HTTP notification bus instance', function (): void {
    if (!function_exists('pcntl_fork') || !function_exists('pcntl_waitpid')) {
        $this->markTestSkipped('pcntl is required for the cross-process subscription test.');
    }

    $root = kirbyMcpStatelessRoot();
    $factory = new HttpFactory();
    $handler = kirbyMcpStatelessHandler($root, sseMaxSeconds: 1);

    try {
        $response = $handler->handle(kirbyMcpStatelessRequest(
            $factory,
            'subscriptions/listen',
            9,
            ['notifications' => ['resourceSubscriptions' => ['kirby://site/content']]],
        )->withHeader('Accept', 'text/event-stream'));
        expect($response->getStatusCode())->toBe(200)
            ->and($response->getHeaderLine('Content-Type'))->toStartWith('text/event-stream')
            ->and($response->getHeaderLine('Mcp-Session-Id'))->toBe('');

        $child = pcntl_fork();
        if ($child === -1) {
            throw new RuntimeException('Unable to fork notification publisher.');
        }
        if ($child === 0) {
            usleep(400000);
            try {
                $publisher = new FileNotificationBus($root);
                $publisher->activate();
                $publisher->publish(new ResourceUpdatedNotification('kirby://site/content'));
                exit(0);
            } catch (Throwable) {
                exit(1);
            }
        }

        ob_start();
        $response->getBody()->getContents();
        $stream = (string) ob_get_clean();
        pcntl_waitpid($child, $status);

        expect(pcntl_wexitstatus($status))->toBe(0)
            ->and($stream)->toContain('notifications/subscriptions/acknowledged')
            ->and($stream)->toContain('notifications/resources/updated')
            ->and($stream)->toContain('kirby://site/content')
            ->and($stream)->toContain('"resultType":"complete"');
    } finally {
        removeKirbyMcpStatelessRoot($root);
    }
});
