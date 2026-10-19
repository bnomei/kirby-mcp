<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Cli\KirbyCliRunner;
use Bnomei\KirbyMcp\Install\RuntimeCommandsInstaller;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Tools\NativeContentTools;
use Kirby\Filesystem\Dir;

beforeEach(function (): void {
    $this->lifecycleRoot = sys_get_temp_dir() . '/kirby-lifecycle-' . bin2hex(random_bytes(6));
    foreach (['content/home', 'content/alpha/child', 'site/config', 'site/blueprints/pages'] as $path) {
        mkdir($this->lifecycleRoot . '/' . $path, 0700, true);
    }
    symlink(dirname(__DIR__, 2) . '/vendor', $this->lifecycleRoot . '/vendor');
    file_put_contents($this->lifecycleRoot . '/index.php', '<?php require __DIR__ . "/vendor/autoload.php"; echo (new Kirby(["roots" => ["index" => __DIR__]]))->render();');
    file_put_contents($this->lifecycleRoot . '/composer.json', '{"require":{"getkirby/cms":"6.0.0-beta.1"}}');
    file_put_contents($this->lifecycleRoot . '/site/config/config.php', '<?php return [];');
    file_put_contents($this->lifecycleRoot . '/site/blueprints/pages/default.yml', "title: Default\nfields:\n  title:\n    type: text\n");
    foreach (['home', 'alpha', 'alpha/child'] as $slug) {
        file_put_contents($this->lifecycleRoot . '/content/' . $slug . '/default.txt', "Title: $slug\n");
    }
    file_put_contents($this->lifecycleRoot . '/content/alpha/child/a.csv', 'a,b');
    (new RuntimeCommandsInstaller())->install($this->lifecycleRoot, commandsRootOverride: $this->lifecycleRoot . '/site/commands', pluginsRootOverride: $this->lifecycleRoot . '/site/plugins');
    putenv(KirbyCliRunner::ENV_KIRBY_BIN . '=' . realpath(__DIR__ . '/../../vendor/bin/kirby'));
    putenv(ProjectContext::ENV_PROJECT_ROOT . '=' . $this->lifecycleRoot);
    $this->lifecycle = new NativeContentTools();
});

afterEach(function (): void {
    unlink($this->lifecycleRoot . '/vendor');
    Dir::remove($this->lifecycleRoot);
});

it('creates only confirmed drafts and changes slug and native status', function (): void {
    $parent = ['type' => 'site'];
    $preview = $this->lifecycle->createPage($parent, 'new', 'default', ['title' => 'New']);
    expect($preview['ok'])->toBeTrue(json_encode($preview, JSON_THROW_ON_ERROR));
    expect(is_dir($this->lifecycleRoot . '/content/_drafts/new'))->toBeFalse();
    $created = $this->lifecycle->createPage($parent, 'new', 'default', ['title' => 'New'], true);
    expect($created['ok'])->toBeTrue(json_encode($created, JSON_THROW_ON_ERROR));
    expect($created['page']['status'])->toBe('draft');
    $search = new \Bnomei\KirbyMcp\Mcp\Tools\ContentSearchTools();
    $found = $search->pages(status: 'draft', query: 'New');
    expect($found['ok'])->toBeTrue(json_encode($found, JSON_THROW_ON_ERROR));
    expect(array_column($found['items'], 'pathId'))->toBe(['new']);
    $files = $search->files(parent: ['type' => 'page', 'id' => 'alpha'], recursive: true, type: 'document');
    expect($files['ok'])->toBeTrue(json_encode($files, JSON_THROW_ON_ERROR));
    expect(array_column($files['items'], 'pathId'))->toBe(['alpha/child/a.csv']);
    $id = $created['page']['id'];
    expect($this->lifecycle->changePageSlug($id, 'renamed')['needsConfirm'])->toBeTrue();
    expect(is_dir($this->lifecycleRoot . '/content/_drafts/new'))->toBeTrue();
    $renamed = $this->lifecycle->changePageSlug($id, 'renamed', confirm: true);
    expect($renamed['ok'])->toBeTrue(json_encode($renamed, JSON_THROW_ON_ERROR));
    expect($renamed['page']['pathId'])->toBe('renamed');
    foreach (['unlisted', 'listed', 'draft'] as $status) {
        $changed = $this->lifecycle->changePageStatus($id, $status, confirm: true);
        expect($changed['ok'])->toBeTrue(json_encode($changed, JSON_THROW_ON_ERROR));
        expect($changed['page']['status'])->toBe($status);
        expect($changed['updatedUris'])->not->toBeEmpty();
    }
});

it('previews complete deletion without writing UUIDs and refuses implicit recursion', function (): void {
    $before = file_get_contents($this->lifecycleRoot . '/content/alpha/default.txt');
    $preview = $this->lifecycle->deletePage('alpha');
    expect($preview['ok'])->toBeFalse();
    expect($preview['pages'])->toHaveCount(2);
    expect($preview['files'])->toHaveCount(1);
    expect(file_get_contents($this->lifecycleRoot . '/content/alpha/default.txt'))->toBe($before);
    expect(file_exists($this->lifecycleRoot . '/content/alpha/child/a.csv.txt'))->toBeFalse();
    expect($this->lifecycle->deletePage('alpha', confirm: true)['ok'])->toBeFalse();
    expect($this->lifecycle->deleteFile('alpha/child/a.csv')['warnings'])->not->toBeEmpty();
    expect(is_file($this->lifecycleRoot . '/content/alpha/child/a.csv'))->toBeTrue();
    expect($this->lifecycle->deleteFile('alpha/child/a.csv', true)['ok'])->toBeTrue();
    expect(is_file($this->lifecycleRoot . '/content/alpha/child/a.csv'))->toBeFalse();
    $deleted = $this->lifecycle->deletePage('alpha', recursive: true, confirm: true);
    expect($deleted['ok'])->toBeTrue(json_encode($deleted, JSON_THROW_ON_ERROR));
    expect(is_dir($this->lifecycleRoot . '/content/alpha'))->toBeFalse();
});

it('rejects reserved content, invalid slugs, languages and status positions', function (): void {
    foreach (['UUID', 'Template', 'Slug', 'status'] as $key) {
        expect($this->lifecycle->createPage(['type' => 'site'], 'new', 'default', [$key => 'forged'], true)['ok'])->toBeFalse();
    }
    expect($this->lifecycle->createPage(['type' => 'site'], '../escape', 'default', confirm: true)['ok'])->toBeFalse();
    expect($this->lifecycle->changePageSlug('alpha', 'new', 'unknown', true)['ok'])->toBeFalse();
    expect($this->lifecycle->changePageStatus('alpha', 'unlisted', 2, true)['ok'])->toBeFalse();
    expect($this->lifecycle->changePageStatus('alpha', 'listed', 0, true)['ok'])->toBeFalse();
});

it('changes a translated slug without renaming the default page', function (): void {
    mkdir($this->lifecycleRoot . '/site/languages');
    file_put_contents($this->lifecycleRoot . '/site/config/config.php', '<?php return ["languages" => true];');
    foreach (['en', 'de'] as $code) {
        file_put_contents($this->lifecycleRoot . '/site/languages/' . $code . '.php', '<?php return ' . var_export(['code' => $code, 'name' => $code, 'default' => $code === 'en', 'url' => '/' . $code], true) . ';');
    }
    rename($this->lifecycleRoot . '/content/alpha/default.txt', $this->lifecycleRoot . '/content/alpha/default.en.txt');
    foreach (['beta', '_drafts/pending'] as $sibling) {
        mkdir($this->lifecycleRoot . '/content/' . $sibling, 0700, true);
        file_put_contents($this->lifecycleRoot . '/content/' . $sibling . '/default.en.txt', 'Title: Other');
        file_put_contents($this->lifecycleRoot . '/content/' . $sibling . '/default.de.txt', 'Slug: occupied-' . basename($sibling));
        foreach ([false, true] as $confirm) {
            expect($this->lifecycle->changePageSlug('alpha', 'occupied-' . basename($sibling), 'de', $confirm)['ok'])->toBeFalse();
            foreach (['en', 'default', 'current'] as $language) {
                expect($this->lifecycle->changePageSlug('alpha', 'occupied-' . basename($sibling), $language, $confirm)['ok'])->toBeFalse();
            }
        }
    }
    expect(file_exists($this->lifecycleRoot . '/content/alpha/default.de.txt'))->toBeFalse();
    $preview = $this->lifecycle->changePageSlug('alpha', 'anders', 'de');
    expect($preview['ok'])->toBeTrue(json_encode($preview, JSON_THROW_ON_ERROR));
    expect(file_exists($this->lifecycleRoot . '/content/alpha/default.de.txt'))->toBeFalse();
    $changed = $this->lifecycle->changePageSlug('alpha', 'anders', 'de', true);
    expect($changed['ok'])->toBeTrue(json_encode($changed, JSON_THROW_ON_ERROR));
    expect($changed['page']['pathId'])->toBe('alpha');
    expect(file_get_contents($this->lifecycleRoot . '/content/alpha/default.de.txt'))->toContain('Slug: anders');
});

it('checks every descendant and file guard before recursive deletion', function (): void {
    mkdir($this->lifecycleRoot . '/site/blueprints/users');
    file_put_contents($this->lifecycleRoot . '/site/blueprints/users/editor.yml', "title: Editor\npermissions:\n  site:\n    read: true\n  bnomei.kirby-mcp:\n    '*': true\n");
    $process = new \Symfony\Component\Process\Process([PHP_BINARY, '-r', <<<'PHP'
require 'vendor/autoload.php';
$app = new Kirby\Cms\App(['roots' => ['index' => getcwd()]]);
$app->impersonate('kirby', fn () => $app->users()->create(['email' => 'admin@example.com', 'role' => 'admin', 'password' => 'test-password-123']));
$user = $app->impersonate('kirby', fn () => $app->users()->create(['email' => 'editor@example.com', 'role' => 'editor', 'password' => 'test-password-123']));
echo $user->id();
PHP], $this->lifecycleRoot);
    $process->mustRun();
    $tools = new NativeContentTools(new ProjectContext(oauthUserId: trim($process->getOutput())));
    file_put_contents($this->lifecycleRoot . '/site/blueprints/pages/forbidden.yml', "title: Forbidden\noptions:\n  create: false\n");
    expect($tools->createPage(['type' => 'site'], 'denied', 'forbidden', confirm: true)['ok'])->toBeFalse();
    expect(is_dir($this->lifecycleRoot . '/content/_drafts/denied'))->toBeFalse();
    $created = $tools->createPage(['type' => 'site'], 'allowed', 'default', ['title' => 'Allowed'], true);
    expect($created['ok'])->toBeTrue(json_encode($created, JSON_THROW_ON_ERROR));
    file_put_contents($this->lifecycleRoot . '/site/blueprints/pages/locked.yml', "title: Locked\noptions:\n  delete: false\n");
    rename($this->lifecycleRoot . '/content/alpha/child/default.txt', $this->lifecycleRoot . '/content/alpha/child/locked.txt');
    $denied = $tools->deletePage('alpha', recursive: true, confirm: true);
    expect($denied['ok'])->toBeFalse();
    expect(is_file($this->lifecycleRoot . '/content/alpha/child/a.csv'))->toBeTrue();
    rename($this->lifecycleRoot . '/content/alpha/child/locked.txt', $this->lifecycleRoot . '/content/alpha/child/default.txt');
    mkdir($this->lifecycleRoot . '/site/blueprints/files');
    file_put_contents($this->lifecycleRoot . '/site/blueprints/files/locked.yml', "title: Locked\noptions:\n  delete: false\n");
    file_put_contents($this->lifecycleRoot . '/content/alpha/child/a.csv.txt', "Template: locked\n");
    expect($tools->deletePage('alpha', recursive: true, confirm: true)['ok'])->toBeFalse();
    expect(is_dir($this->lifecycleRoot . '/content/alpha'))->toBeTrue();
    unlink($this->lifecycleRoot . '/content/alpha/child/a.csv.txt');
    $allowed = $tools->deletePage('alpha', recursive: true, confirm: true);
    expect($allowed['ok'])->toBeTrue(json_encode($allowed, JSON_THROW_ON_ERROR));
});
