<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Install\RuntimeCommandsInstaller;
use Bnomei\KirbyMcp\Mcp\Http\HttpAuthScopes;
use Bnomei\KirbyMcp\Mcp\HttpMcpHandler;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\ServerFactory;
use GuzzleHttp\Psr7\HttpFactory;
use Kirby\Cms\App;
use Mcp\Server\Session\FileSessionStore;
use Mcp\Server\Transport\Http\OAuth\AuthorizationResult;
use Mcp\Server\Transport\Http\OAuth\AuthorizationTokenValidatorInterface;

function oauthPermissionsCopyTree(string $source, string $destination): void
{
    mkdir($destination, 0777, true);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
    );

    foreach ($iterator as $item) {
        $target = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
        if ($item->isDir()) {
            is_dir($target) || mkdir($target, 0777, true);
        } else {
            copy($item->getPathname(), $target);
        }
    }
}

function oauthPermissionsRemoveTree(string $root): void
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

/** @return array{root: string, allowed: string, restricted: string} */
function oauthPermissionsProject(): array
{
    $root = sys_get_temp_dir() . '/kirby-mcp-oauth-permissions-' . bin2hex(random_bytes(6));
    mkdir($root, 0700);

    foreach (['content', 'kirby', 'site'] as $directory) {
        oauthPermissionsCopyTree(cmsPath() . '/' . $directory, $root . '/' . $directory);
    }
    // The parent test process may already have loaded these global model/plugin classes.
    oauthPermissionsRemoveTree($root . '/site/models');
    oauthPermissionsRemoveTree($root . '/site/plugins');
    foreach (['index.php', 'composer.json'] as $file) {
        copy(cmsPath() . '/' . $file, $root . '/' . $file);
    }
    symlink(dirname(__DIR__, 2) . '/vendor', $root . '/vendor');
    // Composer loads Kirby from vendor; explicitly anchor this isolated site's roots.
    file_put_contents($root . '/index.php', '<?php require __DIR__ . "/vendor/autoload.php"; echo (new Kirby(["roots" => ["index" => __DIR__]]))->render();');
    $role = $root . '/site/blueprints/users/restricted.yml';
    file_put_contents($role, <<<'YAML'
title: Restricted
permissions:
  pages:
    update: false
  files:
    update: false
  site:
    update: false
  users:
    update: false
  bnomei.kirby-mcp:
    "*": true
YAML);
    file_put_contents($root . '/site/blueprints/users/editor.yml', <<<'YAML'
title: Editor
permissions:
  pages:
    update: true
  users:
    update: true
  bnomei.kirby-mcp:
    "*": false
    access: true
    discovery: true
    content: true
    content.pages: true
    content.pages.read: true
    content.pages.update: true
    content.files: true
    content.files.read: true
    content.files.update: true
    content.site: true
    content.site.read: true
    content.site.update: true
    content.users: true
    content.users.read: true
    content.users.update: true
YAML);
    (new RuntimeCommandsInstaller())->install(
        $root,
        commandsRootOverride: $root . '/site/commands',
        pluginsRootOverride: $root . '/site/plugins',
    );

    $previous = App::instance(null, true);
    $previousErrorHandlers = captureErrorHandlers();
    $previousWhoops = App::$enableWhoops;
    App::$enableWhoops = false;
    $previousPlugins = $previous?->plugins() ?? [];
    $previous?->plugins([]);
    $app = new App(['roots' => ['index' => $root]]);
    try {
        $allowed = $app->impersonate('kirby', static fn () => $app->users()->create([
            'email' => 'oauth-allowed@example.com',
            'password' => 'test1234',
            'role' => 'editor',
        ]));
        $restricted = $app->impersonate('kirby', static fn () => $app->users()->create([
            'email' => 'oauth-restricted@example.com',
            'password' => 'test1234',
            'role' => 'restricted',
        ]));

        return ['root' => $root, 'allowed' => $allowed->id(), 'restricted' => $restricted->id()];
    } finally {
        $app->plugins($previousPlugins);
        App::instance($previous, true);
        App::$enableWhoops = $previousWhoops;
        restoreErrorHandlers($previousErrorHandlers);
    }
}

/** @param array<string, string> $subjects */
function oauthPermissionsHandler(string $root, array $subjects, bool $useKirbyUser = true): HttpMcpHandler
{
    $validator = new class ($subjects) implements AuthorizationTokenValidatorInterface {
        /** @param array<string, string> $subjects */
        public function __construct(private readonly array $subjects)
        {
        }

        public function validate(string $accessToken): AuthorizationResult
        {
            if (!array_key_exists($accessToken, $this->subjects)) {
                return AuthorizationResult::unauthorized('invalid_token');
            }

            return AuthorizationResult::allow([
                'oauth.scopes' => HttpAuthScopes::all(),
                'oauth.subject' => $this->subjects[$accessToken],
            ]);
        }
    };

    return new HttpMcpHandler(
        serverFactory: new ServerFactory(),
        sessionStore: new FileSessionStore($root . '/http-sessions'),
        tokenValidator: $validator,
        projectRoot: $root,
        useKirbyUser: $useKirbyUser,
    );
}

/** @param array<string, mixed> $arguments */
function oauthPermissionsCall(
    HttpMcpHandler $handler,
    string $token,
    string $tool,
    array $arguments,
    int $id,
    array $meta = [],
): array {
    $factory = new HttpFactory();
    $params = [
        'name' => $tool,
        'arguments' => $arguments,
        '_meta' => array_merge([
            'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
            'io.modelcontextprotocol/clientCapabilities' => new stdClass(),
            'io.modelcontextprotocol/clientInfo' => ['name' => 'tests', 'version' => 'dev'],
        ], $meta),
    ];
    $body = json_encode([
        'jsonrpc' => '2.0',
        'id' => $id,
        'method' => 'tools/call',
        'params' => $params,
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    $response = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('MCP-Protocol-Version', '2026-07-28')
            ->withHeader('Mcp-Method', 'tools/call')
            ->withHeader('Mcp-Name', $tool)
            ->withBody($factory->createStream($body)),
    );

    if ($response->getStatusCode() !== 200) {
        throw new RuntimeException('Unexpected HTTP response: ' . $response->getStatusCode() . ' ' . (string) $response->getBody());
    }
    $payload = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);

    return $payload['result']['structuredContent'] ?? [];
}

/** @param array<string, mixed> $params */
function oauthPermissionsRequest(
    HttpMcpHandler $handler,
    string $token,
    string $method,
    array $params,
    int $id,
): array {
    $factory = new HttpFactory();
    $params['_meta'] = [
        'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
        'io.modelcontextprotocol/clientCapabilities' => new stdClass(),
        'io.modelcontextprotocol/clientInfo' => ['name' => 'tests', 'version' => 'dev'],
    ];
    $body = json_encode([
        'jsonrpc' => '2.0',
        'id' => $id,
        'method' => $method,
        'params' => $params === [] ? new stdClass() : $params,
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    $response = $handler->handle(
        $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('MCP-Protocol-Version', '2026-07-28')
            ->withHeader('Mcp-Method', $method)
            ->withHeader('Mcp-Name', $params['uri'] ?? $params['name'] ?? '')
            ->withBody($factory->createStream($body)),
    );

    return [
        'status' => $response->getStatusCode(),
        'payload' => json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR),
    ];
}

it('enforces the validated OAuth user permissions for all content mutation subprocesses', function (): void {
    $previousErrorHandlers = captureErrorHandlers();
    $fixture = oauthPermissionsProject();
    $previousRoot = getenv(ProjectContext::ENV_PROJECT_ROOT);
    $previousKirbyBin = getenv(KirbyCliRunner::ENV_KIRBY_BIN);
    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $fixture['root']);
    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . dirname(__DIR__, 2) . '/vendor/bin/kirby');

    try {
        $handler = oauthPermissionsHandler($fixture['root'], [
            'allowed-token' => $fixture['allowed'],
            'restricted-token' => $fixture['restricted'],
            'missing-token' => 'deleted-user-id',
        ]);
        $tools = oauthPermissionsRequest($handler, 'allowed-token', 'tools/list', [], 70);
        $toolNames = array_column($tools['payload']['result']['tools'] ?? [], 'name');
        expect($tools['status'])->toBe(200)
            ->and($toolNames)->toContain('kirby_tool_suggest', 'kirby_read_page_content', 'kirby_update_site_content')
            ->and($toolNames)->not()->toContain('kirby_eval', 'kirby_info');

        foreach (['allowed-token' => false, 'restricted-token' => true] as $token => $hasEval) {
            $index = oauthPermissionsRequest($handler, $token, 'resources/read', ['uri' => 'kirby://tools'], 90);
            expect($index['status'])->toBe(200);
            $result = $index['payload']['result'];
            $entries = json_decode($result['contents'][0]['text'], true, flags: JSON_THROW_ON_ERROR);
            expect($result['ttlMs'])->toBe(0)
                ->and($result['cacheScope'])->toBe('private')
                ->and(in_array('kirby_eval', array_column($entries, 'name'), true))->toBe($hasEval);
        }

        $resources = oauthPermissionsRequest($handler, 'allowed-token', 'resources/list', [], 71);
        $resourceUris = array_column($resources['payload']['result']['resources'] ?? [], 'uri');
        expect($resourceUris)->toContain('kirby://tools', 'kirby://site/content')
            ->and($resourceUris)->not()->toContain('kirby://info', 'kirby://uuid/new');

        $templates = oauthPermissionsRequest($handler, 'allowed-token', 'resources/templates/list', [], 72);
        $templateUris = array_column($templates['payload']['result']['resourceTemplates'] ?? [], 'uriTemplate');
        expect($templateUris)->toContain('kirby://page/content/{encodedIdOrUuid}', 'kirby://file/content/{encodedIdOrUuid}')
            ->and($templateUris)->not()->toContain('kirby://config/{option}', 'kirby://field/{type}');

        foreach ([
            ['tools/call', ['name' => 'kirby_eval', 'arguments' => ['code' => 'return 1;', 'confirm' => true]]],
            ['resources/read', ['uri' => 'kirby://info']],
            ['completion/complete', ['ref' => ['type' => 'ref/resource', 'uri' => 'kirby://config/{option}'], 'argument' => ['name' => 'option', 'value' => '']]],
            ['resources/subscribe', ['uri' => 'kirby://info']],
        ] as $index => [$method, $params]) {
            $denied = oauthPermissionsRequest($handler, 'allowed-token', $method, $params, 80 + $index);
            expect($denied['status'])->toBe(403, $method)
                ->and($denied['payload']['error']['message'] ?? '')->toContain('permission denied');
        }

        $calls = [
            ['kirby_update_page_content', ['id' => 'home', 'data' => ['headline' => 'OAuth page'], 'payloadValidatedWithFieldSchemas' => true, 'confirm' => true]],
            ['kirby_update_site_content', ['data' => ['title' => 'OAuth site'], 'payloadValidatedWithFieldSchemas' => true, 'confirm' => true]],
            ['kirby_update_file_content', ['id' => 'file://mHEVVr6xtDc3gIip', 'data' => ['alt' => 'OAuth file'], 'payloadValidatedWithFieldSchemas' => true, 'confirm' => true]],
            ['kirby_update_user_content', ['id' => $fixture['allowed'], 'data' => ['city' => 'OAuth city'], 'payloadValidatedWithFieldSchemas' => true, 'confirm' => true]],
        ];

        foreach ($calls as $index => [$tool, $arguments]) {
            $allowed = oauthPermissionsCall($handler, 'allowed-token', $tool, $arguments, $index * 2 + 2);
            $field = array_key_first($arguments['data']);
            expect($allowed['ok'] ?? null)->toBeTrue()
                ->and($allowed['content'][$field] ?? null)->toBe($arguments['data'][$field]);

            $arguments['data'][$field] = 'Must not be written';
            $denied = oauthPermissionsCall($handler, 'restricted-token', $tool, $arguments, $index * 2 + 1);
            expect($denied['ok'] ?? null)->toBeFalse()
                ->and($denied['error']['message'] ?? '')->not()->toBe('');

            $readTool = str_replace('update', 'read', $tool);
            $readArgs = isset($arguments['id']) ? ['id' => $arguments['id']] : [];
            $read = oauthPermissionsCall($handler, 'allowed-token', $readTool, $readArgs, 20 + $index);
            expect($read['content'][$field] ?? null)->toBe($allowed['content'][$field]);
        }

        $arguments = $calls[0][1];
        expect(oauthPermissionsCall($handler, 'allowed-token', 'kirby_update_page_content', $arguments, 1)['ok'] ?? null)->toBeTrue();
        expect(oauthPermissionsCall($handler, 'restricted-token', 'kirby_update_page_content', $arguments, 2)['ok'] ?? null)->toBeFalse();

        $missing = oauthPermissionsRequest($handler, 'missing-token', 'tools/call', ['name' => 'kirby_update_page_content', 'arguments' => $arguments], 3);
        expect($missing['status'])->toBe(403);

        $spoofed = oauthPermissionsCall($handler, 'restricted-token', 'kirby_update_page_content', $arguments, 4, [
            'oauth.subject' => $fixture['allowed'],
            'subject' => $fixture['allowed'],
        ]);
        expect($spoofed['ok'] ?? null)->toBeFalse();

        $trusted = oauthPermissionsHandler($fixture['root'], ['local-token' => 'not-a-user'], useKirbyUser: false);
        expect(oauthPermissionsCall($trusted, 'local-token', 'kirby_update_page_content', $arguments, 5)['ok'] ?? null)->toBeTrue();

        $editorRole = file_get_contents($fixture['root'] . '/site/blueprints/users/editor.yml');
        if (!is_string($editorRole)) {
            throw new RuntimeException('Cannot read editor role fixture.');
        }
        file_put_contents($fixture['root'] . '/site/blueprints/users/editor.yml', str_replace('content.pages.update: true', 'content.pages.update: false', $editorRole));
        $changedIndex = oauthPermissionsRequest($handler, 'allowed-token', 'resources/read', ['uri' => 'kirby://tools'], 91)['payload']['result'];
        $changedEntries = json_decode($changedIndex['contents'][0]['text'], true, flags: JSON_THROW_ON_ERROR);
        expect($changedIndex['ttlMs'])->toBe(0)
            ->and($changedIndex['cacheScope'])->toBe('private')
            ->and(array_column($changedEntries, 'name'))->not()->toContain('kirby_update_page_content');
        file_put_contents($fixture['root'] . '/site/blueprints/users/editor.yml', "title: Editor\npermissions: false\n");
        expect(oauthPermissionsRequest($handler, 'allowed-token', 'tools/call', ['name' => 'kirby_update_page_content', 'arguments' => $arguments], 6)['status'])->toBe(403);
        file_put_contents($fixture['root'] . '/site/blueprints/users/editor.yml', $editorRole);
        expect(oauthPermissionsCall($handler, 'allowed-token', 'kirby_update_page_content', $arguments, 7)['ok'] ?? null)->toBeTrue();

        // Dynamic options must be evaluated as the actor, not the CLI's initial user.
        foreach (['pages/home', 'site', 'files/blocks/image', 'users/editor', 'users/restricted'] as $blueprint) {
            unlink($fixture['root'] . '/site/blueprints/' . $blueprint . '.yml');
        }
        mkdir($fixture['root'] . '/site/plugins/dynamic-permissions', 0777, true);
        file_put_contents($fixture['root'] . '/site/plugins/dynamic-permissions/index.php', <<<'PHP'
<?php
$blueprints = [];
foreach (['pages/home', 'site', 'files/blocks/image', 'users/editor', 'users/restricted'] as $name) {
    $blueprints[$name] = static fn (): array => [
        'name' => str_starts_with($name, 'users/') ? basename($name) : $name,
        'title' => 'Dynamic permissions',
        'permissions' => ['bnomei.kirby-mcp' => ['*' => true]],
        'options' => ['update' => kirby()->user()?->email() !== 'oauth-restricted@example.com'],
    ];
}
Kirby::plugin('tests/dynamic-permissions', [
    'blueprints' => $blueprints,
]);
PHP);

        foreach ($calls as $index => [$tool, $arguments]) {
            $field = array_key_first($arguments['data']);
            $arguments['data'][$field] = 'Dynamic permission allowed';
            $allowed = oauthPermissionsCall($handler, 'allowed-token', $tool, $arguments, 30 + $index);
            expect($allowed['ok'] ?? null)->toBeTrue($tool . ': ' . json_encode($allowed))
                ->and($allowed['content'][$field] ?? null)->toBe('Dynamic permission allowed');

            $arguments['data'][$field] = 'Dynamic permission must deny';
            $denied = oauthPermissionsCall($handler, 'restricted-token', $tool, $arguments, 40 + $index);
            expect($denied['ok'] ?? null)->toBeFalse();
            $read = oauthPermissionsCall(
                $handler,
                'allowed-token',
                str_replace('update', 'read', $tool),
                isset($arguments['id']) ? ['id' => $arguments['id']] : [],
                50 + $index
            );
            expect($read['content'][$field] ?? null)->toBe('Dynamic permission allowed');
        }
    } finally {
        $previousRoot === false
            ? putenv(ProjectContext::ENV_PROJECT_ROOT)
            : putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $previousRoot);
        $previousKirbyBin === false
            ? putenv(KirbyCliRunner::ENV_KIRBY_BIN)
            : putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . $previousKirbyBin);
        oauthPermissionsRemoveTree($fixture['root']);
        restoreErrorHandlers($previousErrorHandlers);
    }
});

it('rejects an OAuth token without a usable subject before dispatch', function (string $subject): void {
    $root = sys_get_temp_dir() . '/kirby-mcp-oauth-subject-' . bin2hex(random_bytes(6));
    try {
        $handler = oauthPermissionsHandler($root, ['empty-token' => $subject]);
        $factory = new HttpFactory();
        $response = $handler->handle(
            $factory->createServerRequest('POST', 'http://127.0.0.1/mcp')
                ->withHeader('Authorization', 'Bearer empty-token')
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream('{"jsonrpc":"2.0","id":1,"method":"initialize"}')),
        );
        expect($response->getStatusCode())->toBe(401)
            ->and((string) $response->getBody())->toContain('invalid_token');
    } finally {
        oauthPermissionsRemoveTree($root);
    }
})->with(['', '   ', "\0user-id", "user-id\0suffix"]);
