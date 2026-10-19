<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Commands\ContentSearch;
use Kirby\Cms\App;
use Kirby\Filesystem\Dir;

beforeEach(function (): void {
    $this->searchHandlers = captureErrorHandlers();
    $this->searchRoot = sys_get_temp_dir() . '/kirby-search-' . bin2hex(random_bytes(6));
    foreach (['content/alpha/child', 'content/_drafts/draft', 'content/beta', 'site/blueprints/files', 'site/blueprints/users', 'site/languages'] as $path) {
        mkdir($this->searchRoot . '/' . $path, 0700, true);
    }
    file_put_contents($this->searchRoot . '/site/blueprints/users/editor.yml', "title: Editor\npermissions:\n  files:\n    read: false\n  pages:\n    list: false\n");
    foreach (['alpha' => 'Alpha unique', 'alpha/child' => 'Child unique', '_drafts/draft' => 'Draft unique', 'beta' => 'Beta'] as $path => $title) {
        file_put_contents($this->searchRoot . '/content/' . $path . '/default.en.txt', "Title: $title\n");
        file_put_contents($this->searchRoot . '/content/' . $path . '/default.de.txt', "Title: Deutsch\n");
    }
    foreach (['a.csv', 'alpha/b.csv', 'alpha/child/c.csv'] as $path) {
        file_put_contents($this->searchRoot . '/content/' . $path, 'a,b');
        file_put_contents($this->searchRoot . '/content/' . $path . '.en.txt', "Template: document\n");
    }
    file_put_contents($this->searchRoot . '/content/alpha/b.csv.de.txt', "Caption: Beschreibung\n");
    file_put_contents($this->searchRoot . '/site/blueprints/files/document.yml', "title: Document\nfields:\n  caption:\n    type: text\n");
    foreach (['en', 'de'] as $code) {
        file_put_contents($this->searchRoot . '/site/languages/' . $code . '.php', '<?php return ' . var_export(['code' => $code, 'default' => $code === 'en', 'name' => $code, 'locale' => $code], true) . ';');
    }
    $this->searchApp = new App(['roots' => ['index' => $this->searchRoot], 'options' => ['languages' => true, 'content.uuid' => false]]);
    $this->search = fn (array $input) => $this->searchApp->impersonate('kirby', fn () => ContentSearch::execute($this->searchApp, $input));
});

afterEach(function (): void {
    $this->searchApp->session()->commit();
    Dir::remove($this->searchRoot);
    restoreErrorHandlers($this->searchHandlers);
    restore_exception_handler();
});

it('searches whole-site drafts and direct or recursive parents with stable pagination', function (): void {
    $one = ($this->search)(['operation' => 'find_pages', 'limit' => 1]);
    expect($one['total'])->toBe(4)->and($one['items'][0]['pathId'])->toBe('alpha')->and($one['language'])->toBe('en');
    $two = ($this->search)(['operation' => 'find_pages', 'limit' => 1, 'cursor' => $one['nextCursor']]);
    expect($two['items'][0]['pathId'])->toBe('alpha/child');
    expect(($this->search)(['operation' => 'find_pages', 'parent' => ['type' => 'site']])['total'])->toBe(3);
    expect(($this->search)(['operation' => 'find_pages', 'parent' => ['type' => 'page', 'id' => 'alpha']])['total'])->toBe(1);
    expect(($this->search)(['operation' => 'find_pages', 'status' => 'draft'])['total'])->toBe(1);
    expect(($this->search)(['operation' => 'find_pages', 'query' => 'unique'])['total'])->toBe(3);
    expect(($this->search)(['operation' => 'find_pages', 'query' => 'unique', 'language' => 'de'])['total'])->toBe(0);
    expect(($this->search)(['operation' => 'find_pages', 'template' => 'other'])['total'])->toBe(0);
    expect(fn () => ($this->search)(['operation' => 'find_pages', 'language' => 'de', 'cursor' => $one['nextCursor']]))->toThrow(InvalidArgumentException::class);
});

it('includes own and descendant files and only blueprint-defined missing metadata', function (): void {
    expect(($this->search)(['operation' => 'find_files'])['total'])->toBe(3);
    expect(($this->search)(['operation' => 'find_files', 'parent' => ['type' => 'site']])['total'])->toBe(1);
    $input = ['operation' => 'find_files', 'parent' => ['type' => 'page', 'id' => 'alpha']];
    expect(($this->search)($input)['total'])->toBe(1);
    expect(($this->search)($input + ['recursive' => true])['total'])->toBe(2);
    expect(($this->search)($input + ['missingField' => 'caption'])['total'])->toBe(1);
    expect(($this->search)($input + ['missingField' => 'caption', 'language' => 'de'])['total'])->toBe(0);
    expect(($this->search)($input + ['missingField' => 'undefined'])['total'])->toBe(0);
    expect(($this->search)($input + ['template' => 'document', 'type' => 'image'])['total'])->toBe(0);
});

it('rejects invalid and arbitrary search inputs', function (): void {
    foreach ([['limit' => 0], ['limit' => 101], ['language' => 'xx'], ['status' => 'invalid'], ['filterBy' => 'title'], ['recursive' => 'yes'], ['cursor' => 'bad']] as $filter) {
        expect(fn () => ($this->search)(['operation' => 'find_pages'] + $filter))->toThrow(InvalidArgumentException::class);
    }
});

it('rejects searches when native parent access is denied', function (): void {
    $this->searchApp->impersonate('kirby', fn () => $this->searchApp->users()->create(['email' => 'admin@example.com', 'role' => 'admin', 'password' => 'test-password-123']));
    $user = $this->searchApp->impersonate('kirby', fn () => $this->searchApp->users()->create(['email' => 'editor@example.com', 'role' => 'editor', 'password' => 'test-password-123']));
    foreach (['find_pages', 'find_files'] as $operation) {
        expect(fn () => $this->searchApp->impersonate($user->id(), fn () => ContentSearch::execute($this->searchApp, ['operation' => $operation])))->toThrow(\Kirby\Exception\PermissionException::class);
    }
});
