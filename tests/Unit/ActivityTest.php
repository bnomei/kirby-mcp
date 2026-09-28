<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Activity;
use Bnomei\KirbyMcp\Mcp\Handlers\ActivityHandler;
use Bnomei\KirbyMcp\Mcp\Handlers\RequireInitForToolsHandler;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Enum\CacheScope;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Schema\JsonRpc\Request;
use Mcp\Schema\JsonRpc\Response;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Request\ReadResourceRequest;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\Result\ReadResourceResult;
use Mcp\Server\Handler\Request\RequestHandlerInterface;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;
use Mcp\Server\Session\SessionInterface;

/** @return array{root: string, activity: Activity, path: string} */
function activityProject(bool $enabled): array
{
    $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kirby-mcp-activity-' . bin2hex(random_bytes(8));
    $directory = $root . DIRECTORY_SEPARATOR . '.kirby-mcp';
    mkdir($directory, 0777, true);
    file_put_contents($directory . DIRECTORY_SEPARATOR . 'config.json', json_encode([
        'activity' => ['enabled' => $enabled],
    ], JSON_THROW_ON_ERROR));

    return [
        'root' => $root,
        'activity' => new Activity($root),
        'path' => $directory . DIRECTORY_SEPARATOR . 'activity',
    ];
}

function removeActivityProject(string $root): void
{
    $directory = $root . DIRECTORY_SEPARATOR . '.kirby-mcp';
    $activity = $directory . DIRECTORY_SEPARATOR . 'activity';
    if (is_file($activity)) {
        unlink($activity);
    }
    unlink($directory . DIRECTORY_SEPARATOR . 'config.json');
    rmdir($directory);
    rmdir($root);
}

/**
 * @param Response<mixed>|Error $response
 * @return RequestHandlerInterface<mixed>
 */
function activityDelegate(Response|Error $response): RequestHandlerInterface
{
    return new class ($response) implements RequestHandlerInterface {
        /** @param Response<mixed>|Error $response */
        public function __construct(private readonly Response|Error $response)
        {
        }

        public function supports(Request $request): bool
        {
            return $request instanceof CallToolRequest || $request instanceof ReadResourceRequest;
        }

        public function handle(Request $request, SessionInterface $session): Response|Error
        {
            return $this->response;
        }
    };
}

it('does not create an activity file when disabled', function (): void {
    $project = activityProject(false);

    try {
        $project['activity']->record();

        expect($project['path'])->not->toBeFile()
            ->and($project['activity']->status(1000))->toBe(['state' => 'hidden', 'ageSeconds' => null]);
    } finally {
        removeActivityProject($project['root']);
    }
});

it('skips a busy activity file without blocking or refreshing it', function (): void {
    $project = activityProject(true);
    file_put_contents($project['path'], '990');
    $lock = fopen($project['path'], 'r+');
    expect($lock)->not->toBeFalse();
    if ($lock === false) {
        throw new RuntimeException('Unable to open test activity file.');
    }
    flock($lock, LOCK_EX);

    try {
        $project['activity']->record();
        expect($project['activity']->status(1000))->toBe(['state' => 'hidden', 'ageSeconds' => null])
            ->and(file_get_contents($project['path']))->toBe('990');
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
        removeActivityProject($project['root']);
    }
});

it('records activity and reports exact age boundaries', function (int $age, string $state, ?int $reportedAge): void {
    $project = activityProject(true);

    try {
        $before = time();
        $project['activity']->record();
        $recorded = (int) file_get_contents($project['path']);

        expect($recorded)->toBeGreaterThanOrEqual($before)
            ->toBeLessThanOrEqual(time());

        file_put_contents($project['path'], (string) (1000 - $age));
        expect($project['activity']->status(1000))->toBe([
            'state' => $state,
            'ageSeconds' => $reportedAge,
        ]);
    } finally {
        removeActivityProject($project['root']);
    }
})->with([
    'active at 29 seconds' => [29, 'active', 29],
    'recent at 30 seconds' => [30, 'recent', 30],
    'recent at 119 seconds' => [119, 'recent', 119],
    'idle at 120 seconds' => [120, 'idle', 120],
    'idle at 299 seconds' => [299, 'idle', 299],
    'hidden at 300 seconds' => [300, 'hidden', null],
]);

it('hides malformed and future activity without refreshing timestamps on reads', function (string $value): void {
    $project = activityProject(true);

    try {
        file_put_contents($project['path'], $value);

        expect($project['activity']->status(1000))->toBe(['state' => 'hidden', 'ageSeconds' => null])
            ->and(file_get_contents($project['path']))->toBe($value);
    } finally {
        removeActivityProject($project['root']);
    }
})->with([
    'malformed' => ['not-a-timestamp'],
    'future' => ['1001'],
]);

it('records successful tool results and returns the delegate response unchanged', function (): void {
    $project = activityProject(true);

    try {
        $result = CallToolResult::success([new TextContent('ok')], ['cache' => 'preserved']);
        $response = new Response(7, $result);
        $handler = new ActivityHandler(activityDelegate($response), $project['activity']);
        $request = (new CallToolRequest('kirby_search', []))->withId(7);

        expect($handler->supports($request))->toBeTrue();
        $returned = $handler->handle($request, new Session(new InMemorySessionStore(60)));

        expect($returned)->toBe($response)
            ->and($returned->result)->toBe($result)
            ->and($returned->result->meta)->toBe(['cache' => 'preserved'])
            ->and($project['path'])->toBeFile();
    } finally {
        removeActivityProject($project['root']);
    }
});

it('records successful resource reads without replacing cache metadata', function (): void {
    $project = activityProject(true);

    try {
        $result = new ReadResourceResult([], 1234, CacheScope::Private);
        $response = new Response(8, $result);
        $handler = new ActivityHandler(activityDelegate($response), $project['activity']);
        $returned = $handler->handle(
            (new ReadResourceRequest('kirby://kb'))->withId(8),
            new Session(new InMemorySessionStore(60)),
        );

        expect($returned)->toBe($response)
            ->and($returned->result)->toBe($result)
            ->and($returned->result->ttlMs)->toBe(1234)
            ->and($returned->result->cacheScope)->toBe(CacheScope::Private)
            ->and($project['path'])->toBeFile();
    } finally {
        removeActivityProject($project['root']);
    }
});

it('does not record failed tool results or JSON-RPC errors', function (Response|Error $response): void {
    $project = activityProject(true);

    try {
        file_put_contents($project['path'], '100');
        $handler = new ActivityHandler(activityDelegate($response), $project['activity']);
        $returned = $handler->handle(
            (new CallToolRequest('kirby_search', []))->withId(9),
            new Session(new InMemorySessionStore(60)),
        );

        expect($returned)->toBe($response)
            ->and(file_get_contents($project['path']))->toBe('100');
    } finally {
        removeActivityProject($project['root']);
    }
})->with([
    'tool error' => [new Response(9, CallToolResult::error([new TextContent('failed')]))],
    'protocol error' => [Error::forInternalError('failed', 9)],
]);

it('does not record legacy tool calls denied by the init gate', function (): void {
    $project = activityProject(true);

    try {
        $success = new Response(10, CallToolResult::success([new TextContent('ok')]));
        $activityHandler = new ActivityHandler(activityDelegate($success), $project['activity']);
        $handler = new RequireInitForToolsHandler($activityHandler);

        $returned = $handler->handle(
            (new CallToolRequest('kirby_search', []))->withId(10),
            new Session(new InMemorySessionStore(60)),
        );

        expect($returned)->toBeInstanceOf(Response::class)
            ->and($returned->result)->toBeInstanceOf(CallToolResult::class)
            ->and($returned->result->isError)->toBeTrue()
            ->and($project['path'])->not->toBeFile();
    } finally {
        removeActivityProject($project['root']);
    }
});
