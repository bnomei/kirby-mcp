<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Install\RuntimeCommandsInstaller;
use Bnomei\KirbyMcp\Mcp\Http\HttpAuthFactory;
use Bnomei\KirbyMcp\Mcp\Http\HttpAuthScopes;
use Bnomei\KirbyMcp\Mcp\HttpMcpHandler;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Resources\NativeContentResources;
use Bnomei\KirbyMcp\Mcp\ServerFactory;
use Bnomei\KirbyMcp\Mcp\Tools\NativeContentTools;
use GuzzleHttp\Psr7\ServerRequest;
use Kirby\Filesystem\Dir;
use Mcp\Schema\Content\ImageContent;
use Mcp\Schema\Content\TextResourceContents;
use Mcp\Server\Session\InMemorySessionStore;

beforeEach(function (): void {
    $this->nativeRoot = sys_get_temp_dir() . '/kirby-native-' . bin2hex(random_bytes(6));
    foreach (['content/home', 'content/alpha', 'content/beta', 'site/config', 'site/blueprints/pages', 'site/blueprints/files'] as $path) {
        mkdir($this->nativeRoot . '/' . $path, 0700, true);
    }
    symlink(dirname(__DIR__, 2) . '/vendor', $this->nativeRoot . '/vendor');
    file_put_contents($this->nativeRoot . '/index.php', '<?php require __DIR__ . "/vendor/autoload.php"; echo (new Kirby(["roots" => ["index" => __DIR__]]))->render();');
    file_put_contents($this->nativeRoot . '/composer.json', '{"require":{"getkirby/cms":"6.0.0-beta.1"}}');
    file_put_contents($this->nativeRoot . '/site/config/config.php', '<?php return ["bnomei.kirby-mcp.files.readMaxBytes" => 1024, "bnomei.kirby-mcp.files.uploadMaxBytes" => 4096];');
    foreach (['home', 'alpha', 'beta'] as $slug) {
        file_put_contents($this->nativeRoot . '/content/' . $slug . '/default.txt', "Title: $slug\n\n----\n\nUuid: native-$slug\n");
    }
    file_put_contents($this->nativeRoot . '/site/blueprints/pages/default.yml', <<<'YAML'
title: Default
fields:
  attachments:
    type: files
    uploads:
      template: document
  disabled:
    type: files
    uploads: false
  children:
    type: pagelist
    templates: default
YAML);
    file_put_contents($this->nativeRoot . '/site/blueprints/site.yml', "title: Site\nfields:\n  children:\n    type: pagelist\n    templates: default\n  attachments:\n    type: files\n    uploads:\n      template: document\n");
    file_put_contents($this->nativeRoot . '/site/blueprints/files/document.yml', "title: Document\naccept:\n  extension: csv\nfields:\n  caption:\n    type: text\n");
    file_put_contents($this->nativeRoot . '/content/alpha/a.csv', "name,count\r\nÉire,7\r\n");
    file_put_contents($this->nativeRoot . '/content/alpha/z.csv', "name,count\nZ,9\n");
    file_put_contents($this->nativeRoot . '/content/alpha/large.csv', str_repeat('x', 1025));
    $image = imagecreatetruecolor(300, 120);
    imagefill($image, 0, 0, 0x7828c8);
    imagepng($image, $this->nativeRoot . '/content/alpha/color.png');
    (new RuntimeCommandsInstaller())->install($this->nativeRoot, commandsRootOverride: $this->nativeRoot . '/site/commands', pluginsRootOverride: $this->nativeRoot . '/site/plugins');
    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . realpath(__DIR__ . '/../../vendor/bin/kirby'));
    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $this->nativeRoot);
    $this->native = new NativeContentTools();
});

afterEach(function (): void {
    unlink($this->nativeRoot . '/vendor');
    Dir::remove($this->nativeRoot);
});

it('browses paginated native models and follows resource links without mixing filters', function (): void {
    file_put_contents($this->nativeRoot . '/content/alpha/a2.csv', 'two');
    file_put_contents($this->nativeRoot . '/content/alpha/a10.csv', 'ten');
    $one = $this->native->children(['type' => 'site'], limit: 1);
    expect($one['ok'])->toBeTrue();
    expect($one['items'])->toHaveCount(1);
    expect($one['items'][0]['id'])->toBe('page://native-alpha');
    $two = $this->native->children(['type' => 'site'], limit: 1, cursor: $one['nextCursor']);
    expect($two['items'][0]['pathId'])->toBe('beta');
    expect($this->native->children(['type' => 'site'], status: 'draft', cursor: $one['nextCursor'])['ok'])->toBeFalse();
    $files = $this->native->files(['type' => 'page', 'id' => 'page://native-alpha'], type: 'document', limit: 1);
    expect($files['ok'])->toBeTrue(json_encode($files, JSON_THROW_ON_ERROR));
    expect($files['items'][0]['filename'])->toBe('a.csv');
    $parts = explode('/', substr($files['nextUri'], strlen('kirby://browse/files/')));
    $next = (new NativeContentResources())->browseFiles(...$parts);
    expect($next['items'][0]['filename'])->toBe('a10.csv');
    $seen = ['a.csv'];
    while (true) {
        $seen[] = $next['items'][0]['filename'];
        if ($next['nextUri'] === null) {
            break;
        }
        $parts = explode('/', substr($next['nextUri'], strlen('kirby://browse/files/')));
        $next = (new NativeContentResources())->browseFiles(...$parts);
    }
    expect($seen)->toBe(['a.csv', 'a10.csv', 'a2.csv', 'large.csv', 'z.csv']);
});

it('reads faithful text and binary resources and returns a bounded MCP image', function (): void {
    $read = $this->native->read('alpha/a.csv');
    expect($read->isError)->toBeFalse(json_encode($read->structuredContent, JSON_THROW_ON_ERROR));
    expect($read->content[1]->resource)->toBeInstanceOf(TextResourceContents::class);
    expect($read->content[1]->resource->text)->toBe("name,count\r\nÉire,7\r\n");
    $resource = (new NativeContentResources())->read(rawurlencode($read->structuredContent['file']['id']));
    expect($resource->text)->toBe("name,count\r\nÉire,7\r\n");
    $large = $this->native->read('alpha/large.csv');
    expect($large->isError)->toBeTrue();
    expect($large->structuredContent['error']['code'])->toBe('size_limit');
    $binary = $this->native->read('alpha/color.png');
    expect(base64_decode($binary->content[1]->resource->blob))->toBe(file_get_contents($this->nativeRoot . '/content/alpha/color.png'));
    $preview = $this->native->image('alpha/color.png', 80, 60);
    expect($preview->isError)->toBeFalse();
    expect($preview->content[1])->toBeInstanceOf(ImageContent::class);
    $dimensions = getimagesizefromstring(base64_decode($preview->content[1]->data));
    expect($dimensions[0])->toBe(80);
    expect($dimensions[1])->toBe(32);
    expect($preview->structuredContent['original'])->toBe(['width' => 300, 'height' => 120]);
    expect($this->native->image('alpha/color.png', 2049)->isError)->toBeTrue();
});

it('fails image previews without saving metadata when thumbnails fall back to the original', function (): void {
    mkdir($this->nativeRoot . '/site/plugins/thumb-fallback');
    file_put_contents($this->nativeRoot . '/site/plugins/thumb-fallback/index.php', <<<'PHP'
<?php
Kirby::plugin('test/thumb-fallback', ['components' => [
    'file::version' => static fn ($kirby, $file, $options) => $file,
]]);
PHP);
    $metadata = $this->nativeRoot . '/content/alpha/color.png.txt';
    file_put_contents($metadata, "Uuid: native-color\n\n----\n\nAlt: Original alt\n");
    $before = file_get_contents($metadata);
    $original = file_get_contents($this->nativeRoot . '/content/alpha/color.png');
    $preview = $this->native->image('alpha/color.png', 80, 60);
    expect($preview->isError)->toBeTrue();
    expect(array_filter($preview->content, fn ($content) => $content instanceof ImageContent))->toBeEmpty();
    expect(file_get_contents($metadata))->toBe($before);
    expect(file_get_contents($this->nativeRoot . '/content/alpha/color.png'))->toBe($original);
});

it('rejects reserved upload metadata before preview or ticket issuance and retains template capacity after reload', function (): void {
    file_put_contents($this->nativeRoot . '/site/blueprints/pages/default.yml', "title: Default\nfields:\n  uploads:\n    type: filelist\n    template: document\n    max: 1\n");
    $source = $this->nativeRoot . '/source.csv';
    file_put_contents($source, "a,b\n1,2\n");
    $parent = ['type' => 'page', 'id' => 'beta'];
    $remote = new NativeContentTools(new ProjectContext(uploadUrl: 'http://localhost/mcp/upload'));
    foreach (['template', 'Template', 'tEmPlAtE', 'uuid', 'UUID'] as $key) {
        $content = [$key => 'forged'];
        expect($this->native->upload($parent, 'one.csv', 'uploads', $content, source: $source)['ok'])->toBeFalse($key);
        expect($this->native->upload($parent, 'one.csv', 'uploads', $content, source: $source, confirm: true)['ok'])->toBeFalse($key);
        expect($remote->upload($parent, 'one.csv', 'uploads', $content, confirm: true)['ok'])->toBeFalse($key);
    }
    expect(glob($this->nativeRoot . '/.kirby-mcp/uploads/*.json') ?: [])->toBeEmpty();
    expect(file_exists($this->nativeRoot . '/content/beta/one.csv'))->toBeFalse();
    $created = $this->native->upload($parent, 'one.csv', 'uploads', ['caption' => 'Allowed'], source: $source, confirm: true);
    expect($created['ok'])->toBeTrue();
    // Each tool call boots a fresh runtime, so persisted template ownership is checked.
    $listed = $this->native->files($parent, template: 'document');
    expect(array_column($listed['items'], 'filename'))->toBe(['one.csv']);
    expect($listed['items'][0]['uuid'])->toBe($created['file']['uuid']);
    expect($this->native->upload($parent, 'two.csv', 'uploads', source: $source, confirm: true)['ok'])->toBeFalse();
});

it('requires runtime scope for original files whose IDs contain update-schema', function (): void {
    file_put_contents($this->nativeRoot . '/content/update-schema.csv', 'site-secret');
    mkdir($this->nativeRoot . '/content/update-schema');
    file_put_contents($this->nativeRoot . '/content/update-schema/default.txt', 'Title: Schema');
    file_put_contents($this->nativeRoot . '/content/update-schema/private.csv', 'page-secret');
    $readOnly = new HttpMcpHandler(new ServerFactory(), new InMemorySessionStore(), tokenValidator: (new HttpAuthFactory())->sharedTokenValidator('test-secret', [HttpAuthScopes::READ]));
    $allowed = new HttpMcpHandler(new ServerFactory(), new InMemorySessionStore(), sharedToken: 'test-secret');
    foreach (['update-schema.csv' => 'site-secret', 'update-schema%2Fprivate.csv' => 'page-secret'] as $id => $bytes) {
        $uri = 'kirby://file/read/' . $id;
        $params = ['uri' => $uri, '_meta' => ['io.modelcontextprotocol/protocolVersion' => '2026-07-28', 'io.modelcontextprotocol/clientCapabilities' => new stdClass(), 'io.modelcontextprotocol/clientInfo' => ['name' => 'tests', 'version' => '1']]];
        $request = new ServerRequest('POST', 'http://localhost/mcp', ['Authorization' => 'Bearer test-secret', 'Content-Type' => 'application/json', 'MCP-Protocol-Version' => '2026-07-28', 'Mcp-Method' => 'resources/read', 'Mcp-Name' => $uri], json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'resources/read', 'params' => $params], JSON_THROW_ON_ERROR));
        $response = $readOnly->handle($request);
        expect($response->getStatusCode())->toBe(403);
        expect((string)$response->getBody())->not->toContain($bytes);
        $request->getBody()->rewind();
        $response = $allowed->handle($request);
        expect($response->getStatusCode())->toBe(200, (string)$response->getBody());
        expect((string)$response->getBody())->toContain($bytes);
    }
});

it('rejects filesystem paths, traversal and symlink aliases when reading Kirby files', function (): void {
    file_put_contents($this->nativeRoot . '/private.csv', 'outside-content-secret');
    file_put_contents($this->nativeRoot . '/content/beta/private.csv', 'other-page-secret');
    symlink($this->nativeRoot . '/private.csv', $this->nativeRoot . '/content/alpha/outside.csv');
    symlink($this->nativeRoot . '/content/beta/private.csv', $this->nativeRoot . '/content/alpha/alias.csv');
    foreach ([$this->nativeRoot . '/private.csv', '../private.csv', 'alpha/../../private.csv',
        'file://' . $this->nativeRoot . '/private.csv', 'alpha/outside.csv', 'alpha/alias.csv'] as $id) {
        $read = $this->native->read($id);
        expect($read->isError)->toBeTrue($id);
        expect(json_encode($read, JSON_THROW_ON_ERROR))->not->toContain('outside-content-secret', 'other-page-secret');
    }
    symlink($this->nativeRoot . '/content/alpha/color.png', $this->nativeRoot . '/content/beta/alias.png');
    expect($this->native->image('beta/alias.png')->isError)->toBeTrue();
    expect($this->native->read('alpha/a.csv')->isError)->toBeFalse();
});

it('copies local uploads with metadata and rejects disabled contexts, types and collisions', function (): void {
    $source = $this->nativeRoot . '/source.csv';
    file_put_contents($source, "label,count\nNew,17\n");
    $parent = ['type' => 'page', 'id' => 'alpha'];
    expect($this->native->upload($parent, 'new.csv', source: $source)['ok'])->toBeFalse();
    expect($this->native->upload($parent, 'new.csv', 'disabled', source: $source)['ok'])->toBeFalse();
    $preview = $this->native->upload($parent, 'new.csv', 'attachments', ['caption' => 'Initial caption'], source: $source);
    expect($preview['needsConfirm'])->toBeTrue();
    expect(file_exists($this->nativeRoot . '/content/alpha/new.csv'))->toBeFalse();
    $created = $this->native->upload($parent, 'new.csv', 'attachments', ['caption' => 'Initial caption'], source: $source, confirm: true);
    expect($created['ok'])->toBeTrue(json_encode($created, JSON_THROW_ON_ERROR));
    expect($created['file']['template'])->toBe('document');
    expect(file_get_contents($source))->toBe("label,count\nNew,17\n");
    expect(file_get_contents($this->nativeRoot . '/content/alpha/new.csv'))->toBe(file_get_contents($source));
    expect(file_get_contents($this->nativeRoot . '/content/alpha/new.csv.txt'))->toContain('Caption: Initial caption');
    expect($this->native->upload($parent, 'new.csv', 'attachments', source: $source, confirm: true)['ok'])->toBeFalse();
    expect($this->native->upload($parent, 'wrong.jpg', 'attachments', source: $source, confirm: true)['ok'])->toBeFalse();
});

it('previews and confirms native moves with stable UUIDs and rejects cycles', function (): void {
    $destination = ['type' => 'page', 'id' => 'beta'];
    $preview = $this->native->move('alpha', $destination);
    expect($preview['ok'])->toBeTrue(json_encode($preview, JSON_THROW_ON_ERROR));
    expect($preview['needsConfirm'])->toBeTrue();
    expect(is_dir($this->nativeRoot . '/content/alpha'))->toBeTrue();
    $moved = $this->native->move('page://native-alpha', $destination, true);
    expect($moved['ok'])->toBeTrue();
    expect($moved['page']['pathId'])->toBe('beta/alpha');
    expect($moved['page']['uuid'])->toBe('page://native-alpha');
    expect($this->native->move('beta', ['type' => 'page', 'id' => 'page://native-alpha'], true)['ok'])->toBeFalse();
    $back = $this->native->move('page://native-alpha', ['type' => 'site'], true);
    expect($back['ok'])->toBeTrue();
    expect($back['page']['pathId'])->toBe('alpha');
});

it('redeems authenticated single-use remote upload tickets and rejects server paths and oversized bodies', function (): void {
    $tools = new NativeContentTools(new ProjectContext(uploadUrl: 'http://localhost/mcp/upload'));
    $parent = ['type' => 'page', 'id' => 'alpha'];
    expect($tools->upload($parent, 'remote.csv', 'attachments', source: '/etc/passwd', confirm: true)['ok'])->toBeFalse();
    $ticket = $tools->upload($parent, 'remote.csv', 'attachments', ['caption' => 'Remote caption'], confirm: true);
    expect($ticket['ok'])->toBeTrue();
    $handler = new HttpMcpHandler(new ServerFactory(), new InMemorySessionStore(), sharedToken: 'test-secret');
    $request = new ServerRequest('POST', 'http://localhost/mcp/upload', ['Authorization' => 'Bearer test-secret', 'X-Kirby-Upload-Ticket' => $ticket['ticket']], "label,count\nRemote,23\n");
    expect($handler->handle($request->withoutHeader('Authorization'))->getStatusCode())->toBe(401);
    $response = $handler->handle($request);
    expect((string)$response->getBody())->toContain('"ok":true');
    expect($response->getStatusCode())->toBe(201);
    expect(file_get_contents($this->nativeRoot . '/content/alpha/remote.csv'))->toBe("label,count\nRemote,23\n");
    expect($handler->handle($request)->getStatusCode())->toBe(400);
    $ticket = $tools->upload($parent, 'oversize.csv', 'attachments', confirm: true);
    $request = new ServerRequest('POST', 'http://localhost/mcp/upload', ['Authorization' => 'Bearer test-secret', 'X-Kirby-Upload-Ticket' => $ticket['ticket']], str_repeat('x', 4097));
    expect($handler->handle($request)->getStatusCode())->toBe(400);
    expect(file_exists($this->nativeRoot . '/content/alpha/oversize.csv'))->toBeFalse();
    $readOnly = new HttpMcpHandler(new ServerFactory(), new InMemorySessionStore(), tokenValidator: (new HttpAuthFactory())->sharedTokenValidator('test-secret', [HttpAuthScopes::READ]));
    expect($readOnly->handle($request)->getStatusCode())->toBe(403);
});

it('keeps UUID-disabled browsing usable and enforces file list capacity and destination', function (): void {
    file_put_contents($this->nativeRoot . '/site/config/config.php', '<?php return ["content.uuid" => false];');
    file_put_contents($this->nativeRoot . '/site/blueprints/pages/default.yml', "title: Default\nfields:\n  uploads:\n    type: filelist\n    parent: site.find(\"beta\")\n    template: document\n    max: 1\n");
    $source = $this->nativeRoot . '/source.csv';
    file_put_contents($source, "a,b\n1,2\n");
    $pages = $this->native->children(['type' => 'site']);
    expect($pages['items'][0]['id'])->toBe('alpha');
    expect($pages['items'][0]['uuid'])->toBeNull();
    $parent = ['type' => 'page', 'id' => 'alpha'];
    $uploaded = $this->native->upload($parent, 'one.csv', 'uploads', source: $source, confirm: true);
    expect($uploaded['ok'])->toBeTrue(json_encode($uploaded, JSON_THROW_ON_ERROR));
    expect($uploaded['file']['id'])->toBe('beta/one.csv');
    expect($uploaded['file']['uuid'])->toBeNull();
    expect($this->native->upload($parent, 'two.csv', 'uploads', source: $source, confirm: true)['ok'])->toBeFalse();
    expect(file_exists($this->nativeRoot . '/content/beta/two.csv'))->toBeFalse();
    $read = (new NativeContentResources())->read(rawurlencode('beta/one.csv'));
    expect($read->text)->toBe("a,b\n1,2\n");
});

it('dispatches the new MCP contracts and rechecks a real users native permissions on ticket redemption', function (): void {
    mkdir($this->nativeRoot . '/site/blueprints/users');
    $rolePath = $this->nativeRoot . '/site/blueprints/users/editor.yml';
    $role = "title: Editor\npermissions:\n  site:\n    read: true\n  bnomei.kirby-mcp:\n    '*': true\n";
    file_put_contents($rolePath, $role);
    $createUser = new \Symfony\Component\Process\Process([PHP_BINARY, '-r', <<<'PHP'
require 'vendor/autoload.php';
$app = new Kirby\Cms\App(['roots' => ['index' => getcwd()]]);
$app->impersonate('kirby', fn () => $app->users()->create(['email' => 'admin@example.com', 'role' => 'admin', 'password' => 'test-password-123']));
$user = $app->impersonate('kirby', fn () => $app->users()->create(['email' => 'editor@example.com', 'role' => 'editor', 'password' => 'test-password-123']));
echo $user->id();
PHP], $this->nativeRoot);
    $createUser->mustRun();
    $userId = trim($createUser->getOutput());
    $validator = new class ($userId) implements \Mcp\Server\Transport\Http\OAuth\AuthorizationTokenValidatorInterface {
        public function __construct(private string $id)
        {
        }
        public function validate(string $accessToken): \Mcp\Server\Transport\Http\OAuth\AuthorizationResult
        {
            return \Mcp\Server\Transport\Http\OAuth\AuthorizationResult::allow(new \Mcp\Server\Authorization\AccessToken(HttpAuthScopes::all(), ['sub' => $this->id]));
        }
    };
    $handler = new HttpMcpHandler(new ServerFactory(), new InMemorySessionStore(), tokenValidator: $validator, projectRoot: $this->nativeRoot, useKirbyUser: true);
    $call = function (string $method, array $params, int $status = 200) use ($handler): array {
        $params['_meta'] = ['io.modelcontextprotocol/protocolVersion' => '2026-07-28', 'io.modelcontextprotocol/clientCapabilities' => new stdClass(), 'io.modelcontextprotocol/clientInfo' => ['name' => 'tests', 'version' => '1']];
        $request = new ServerRequest('POST', 'http://localhost/mcp', ['Authorization' => 'Bearer test', 'Content-Type' => 'application/json', 'MCP-Protocol-Version' => '2026-07-28', 'Mcp-Method' => $method, 'Mcp-Name' => $params['name'] ?? $params['uri'] ?? ''], json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params], JSON_THROW_ON_ERROR));
        $response = $handler->handle($request);
        expect($response->getStatusCode())->toBe($status, (string)$response->getBody());
        return json_decode((string)$response->getBody(), true, flags: JSON_THROW_ON_ERROR);
    };
    $listing = $call('tools/call', ['name' => 'kirby_list_files', 'arguments' => ['parent' => ['type' => 'page', 'id' => 'alpha'], 'limit' => 1]]);
    expect($listing['result']['structuredContent']['ok'] ?? null)->toBeTrue(json_encode($listing, JSON_THROW_ON_ERROR));
    expect(array_column($listing['result']['content'], 'type'))->toContain('resource_link');
    foreach (['kirby_find_pages', 'kirby_find_files'] as $name) {
        foreach ([[], ['parent' => null], ['parent' => ['type' => 'site']], ['parent' => ['type' => 'page', 'id' => 'alpha']]] as $arguments) {
            $found = $call('tools/call', ['name' => $name, 'arguments' => $arguments]);
            expect($found['result']['structuredContent']['ok'] ?? null)->toBeTrue(json_encode($found, JSON_THROW_ON_ERROR));
        }
    }
    $file = $listing['result']['structuredContent']['items'][0];
    foreach ([
        'kirby_create_page' => ['parent' => ['type' => 'site'], 'slug' => 'new-draft', 'template' => 'default', 'content' => ['title' => 'New']],
        'kirby_delete_page' => ['id' => 'beta'],
        'kirby_change_page_slug' => ['id' => 'alpha', 'slug' => 'renamed'],
        'kirby_change_page_status' => ['id' => 'alpha', 'status' => 'listed', 'position' => 1],
        'kirby_delete_file' => ['id' => $file['id']],
    ] as $name => $arguments) {
        $preview = $call('tools/call', ['name' => $name, 'arguments' => $arguments]);
        expect($preview['result']['structuredContent']['needsConfirm'] ?? null)->toBeTrue(json_encode($preview, JSON_THROW_ON_ERROR));
    }
    $read = $call('resources/read', ['uri' => $file['readUri']]);
    expect($read['result']['contents'][0]['text'])->toBe("name,count\r\nÉire,7\r\n");
    $image = $call('tools/call', ['name' => 'kirby_view_image', 'arguments' => ['id' => 'alpha/color.png', 'maxWidth' => 80]]);
    expect(array_column($image['result']['content'], 'type'))->toContain('image');
    foreach (["  files:\n    access: false\n", "  pages:\n    access: false\n", "  pages:\n    read: false\n"] as $restriction) {
        file_put_contents($rolePath, $role . $restriction);
        foreach ([$file['id'], $file['pathId']] as $id) {
            $denied = $call('tools/call', ['name' => 'kirby_read_file', 'arguments' => ['id' => $id]]);
            expect($denied['result']['isError'])->toBeTrue();
        }
        $denied = $call('tools/call', ['name' => 'kirby_view_image', 'arguments' => ['id' => 'alpha/color.png']]);
        expect($denied['result']['isError'])->toBeTrue();
    }
    file_put_contents($rolePath, $role);
    $upload = $call('tools/call', ['name' => 'kirby_upload_file', 'arguments' => ['parent' => ['type' => 'page', 'id' => 'alpha'], 'filename' => 'auth.csv', 'uploadContext' => 'attachments', 'content' => ['caption' => 'Auth'], 'confirm' => true]]);
    $ticket = $upload['result']['structuredContent'];
    expect($ticket['ok'])->toBeTrue(json_encode($upload, JSON_THROW_ON_ERROR));
    expect($ticket['uploadUrl'])->toBe('http://localhost/mcp/upload');
    $request = new ServerRequest('POST', $ticket['uploadUrl'], ['Authorization' => 'Bearer test', 'X-Kirby-Upload-Ticket' => $ticket['ticket']], "a,b\n3,4\n");
    // Native create is revoked after issuance, while the MCP capability remains granted.
    file_put_contents($rolePath, $role . "  files:\n    create: false\n    read: false\n  pages:\n    move: false\n");
    expect($handler->handle($request)->getStatusCode())->toBe(400);
    expect(file_exists($this->nativeRoot . '/content/alpha/auth.csv'))->toBeFalse();
    $denied = $call('tools/call', ['name' => 'kirby_read_file', 'arguments' => ['id' => $file['id']]]);
    expect($denied['result']['isError'])->toBeTrue();
    $deniedResource = $call('resources/read', ['uri' => $file['readUri']], 400);
    expect($deniedResource)->toHaveKey('error');
    $deniedMove = $call('tools/call', ['name' => 'kirby_move_page', 'arguments' => ['id' => 'alpha', 'parent' => ['type' => 'page', 'id' => 'beta'], 'confirm' => true]]);
    expect($deniedMove['result']['structuredContent']['ok'])->toBeFalse();
    file_put_contents($rolePath, $role);
    expect($handler->handle($request)->getStatusCode())->toBe(400); // consumed even on failure
    $allowedTools = new NativeContentTools(new ProjectContext(oauthUserId: $userId, uploadUrl: 'http://localhost/mcp/upload'));
    $ticket = $allowedTools->upload(['type' => 'page', 'id' => 'alpha'], 'auth.csv', 'attachments', confirm: true);
    $request = $request->withHeader('X-Kirby-Upload-Ticket', $ticket['ticket']);
    $request->getBody()->rewind();
    expect($handler->handle($request)->getStatusCode())->toBe(201);
});

it('creates translated metadata before hooks and rejects transformed-name collisions', function (): void {
    mkdir($this->nativeRoot . '/site/languages');
    file_put_contents($this->nativeRoot . '/site/languages/en.php', '<?php return ["code" => "en", "default" => true, "name" => "English", "locale" => "en_US"];');
    file_put_contents($this->nativeRoot . '/site/languages/de.php', '<?php return ["code" => "de", "name" => "Deutsch", "locale" => "de_DE"];');
    foreach (['home', 'alpha', 'beta'] as $slug) {
        rename($this->nativeRoot . '/content/' . $slug . '/default.txt', $this->nativeRoot . '/content/' . $slug . '/default.en.txt');
    }
    file_put_contents($this->nativeRoot . '/site/config/config.php', <<<'PHP'
<?php return ['languages' => true, 'hooks' => ['file.create:before' => function ($file, $upload) {
    file_put_contents(kirby()->root('index') . '/hook-caption', $file->content('de')->get('caption')->value());
}]];
PHP);
    $source = $this->nativeRoot . '/source.csv';
    file_put_contents($source, "a,b\n1,2\n");
    $parent = ['type' => 'page', 'id' => 'alpha'];
    $created = $this->native->upload($parent, 'translated.csv', 'attachments', ['caption' => 'Deutsch'], 'de', $source, true);
    expect($created['ok'])->toBeTrue(json_encode($created, JSON_THROW_ON_ERROR));
    expect(file_get_contents($this->nativeRoot . '/hook-caption'))->toBe('Deutsch');
    expect(file_get_contents($this->nativeRoot . '/content/alpha/translated.csv.de.txt'))->toContain('Caption: Deutsch');
    expect(file_get_contents($this->nativeRoot . '/content/alpha/translated.csv.en.txt'))->not->toContain('Caption: Deutsch');
    file_put_contents($this->nativeRoot . '/site/blueprints/files/document.yml', "title: Image\naccept:\n  type: image\ncreate:\n  format: jpg\n");
    $imageSource = $this->nativeRoot . '/content/alpha/color.png';
    file_put_contents($this->nativeRoot . '/content/alpha/converted.jpg', 'existing');
    expect($this->native->upload($parent, 'converted.png', 'attachments', source: $imageSource, confirm: true)['ok'])->toBeFalse();
    expect(file_get_contents($this->nativeRoot . '/content/alpha/converted.jpg'))->toBe('existing');
    expect(file_exists($this->nativeRoot . '/content/alpha/converted.png'))->toBeFalse();
    $converted = $this->native->upload($parent, 'fresh.png', 'attachments', source: $imageSource, confirm: true);
    expect($converted['ok'])->toBeTrue(json_encode($converted, JSON_THROW_ON_ERROR));
    expect($converted['file']['filename'])->toBe('fresh.jpg');
    expect($converted['file']['mime'])->toBe('image/jpeg');
});
