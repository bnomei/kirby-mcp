<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Tools\OnlinePluginsTools;
use Mcp\Exception\ToolCallException;

class OnlinePluginsToolsStub extends OnlinePluginsTools
{
    public int $searchCalls = 0;
    public int $pluginCalls = 0;

    /**
     * @param array<string, string> $pluginHtmlByUrl
     */
    public function __construct(
        private string $searchHtml,
        private array $pluginHtmlByUrl = [],
        private bool $throwOnPlugin = false,
    ) {
    }

    protected function httpGet(string $url, string $accept = 'text/html'): string
    {
        if (str_contains($url, 'plugins.getkirby.com/search')) {
            $this->searchCalls++;
            return $this->searchHtml;
        }

        $this->pluginCalls++;

        if ($this->throwOnPlugin) {
            throw new RuntimeException('boom');
        }

        if (!isset($this->pluginHtmlByUrl[$url])) {
            throw new RuntimeException('Unexpected URL: ' . $url);
        }

        return $this->pluginHtmlByUrl[$url];
    }
}

it('searches the Kirby plugin directory and fetches plugin details', function (): void {
    $searchHtml = <<<'HTML'
<ul class="plugin-cards">
<li>
<a class="plugin-card" href="https://plugins.getkirby.com/acme/foo">
  <span class="plugin-card-title">Foo</span>
  <span class="plugin-card-subtitle">Best &amp; Foo</span>
  <span class="plugin-meta-version" data-version="5">K5</span>
</a>
</li>
</ul>
HTML;

    $pluginHtml = <<<'HTML'
<h1 class="plugin-title">Foo</h1>
<div class="plugin-subtitle text"><p>Foo plugin</p></div>
<div class="plugin-ctas">
  <div class="plugin-cta-links" data-links="1">
    <a class="button" href="https://example.com" target="_blank">Website</a>
  </div>
  <div class="plugin-cta-actions">
    <a class="button" href="https://buy.example.com" target="_blank">Buy</a>
  </div>
</div>
<section class="section plugin-meta">
  <dl>
    <div>
      <dt>Version</dt>
      <dd><a href="https://github.com/acme/foo/releases/tag/v1.2.3" target="_blank">1.2.3</a></dd>
    </div>
    <div>
      <dt>License</dt>
      <dd><a href="https://github.com/acme/foo/blob/main/LICENSE" target="_blank">MIT</a></dd>
    </div>
    <div>
      <dt>Stars</dt>
      <dd>42</dd>
    </div>
    <div>
      <dt>Supports</dt>
      <dd>
        <span class="plugin-meta-versions">
          <span class="plugin-meta-version" title="Supports Kirby 5" data-version="5">K5</span>
          <span class="plugin-meta-version" title="Supports Kirby 4" data-version="4">K4</span>
        </span>
      </dd>
    </div>
    <div>
      <dt>Created</dt>
      <dd>1 Jan 2024</dd>
    </div>
    <div>
      <dt>Updated</dt>
      <dd>2 Feb 2025</dd>
    </div>
  </dl>
</section>
<section class="plugin-features">
  <dl>
    <div class="plugin-feature">
      <dt>Fast</dt>
      <dd>Very fast</dd>
    </div>
  </dl>
</section>
<section class="section plugin-info">
  <dl>
    <div>
      <dt>Topics</dt>
      <dd>
        <ul>
          <li><a href="https://plugins.getkirby.com/topics/foo">Foo</a></li>
        </ul>
      </dd>
    </div>
    <div>
      <dt>Support</dt>
      <dd>
        <a href="mailto:test@example.com">Email</a>
      </dd>
    </div>
    <div>
      <dt>Latest releases</dt>
      <dd>
        <ul>
          <li>
            <a href="https://github.com/acme/foo/releases/tag/v1.2.3" target="_blank"><svg></svg>1.2.3</a>
          </li>
        </ul>
      </dd>
    </div>
  </dl>
</section>
HTML;

    $tools = new OnlinePluginsToolsStub($searchHtml, [
        'https://plugins.getkirby.com/acme/foo' => $pluginHtml,
    ]);

    $payload = $tools->search(' Foo ', kirbyMajorVersion: 5, limit: 10, fetch: 1, maxChars: 20000);

    expect($payload['query'])->toBe('Foo');
    expect($payload['kirbyMajorVersion'])->toBe('5');
    expect($payload['pricing'])->toBe('');
    expect($payload['sort'])->toBe('');

    expect($payload['results'])->toHaveCount(1);
    expect($payload['results'][0]['title'])->toBe('Foo');
    expect($payload['results'][0]['subtitle'])->toBe('Best & Foo');
    expect($payload['results'][0]['supportsKirbyVersions'])->toBe([5]);

    expect($payload['documents'])->toHaveCount(1);

    $doc = $payload['documents'][0];
    expect($doc['error'])->toBeNull();
    expect($doc['markdown'])->toBeString()->not()->toBe('');

    expect($doc['data']['meta']['stars'])->toBe(42);
    expect($doc['data']['meta']['supportsKirbyVersions'])->toBe([4, 5]);
    expect($doc['data']['meta']['version']['value'])->toBe('1.2.3');

    expect($payload['markdown'])->toContain('[Foo](https://plugins.getkirby.com/acme/foo)');
    expect($payload['markdown'])->toContain('v1.2.3');
    expect($payload['markdown'])->toContain('★ 42');

    expect($tools->searchCalls)->toBe(1);
    expect($tools->pluginCalls)->toBe(1);
});

it('records plugin fetch errors without throwing', function (): void {
    $searchHtml = <<<'HTML'
<ul class="plugin-cards">
<li>
<a class="plugin-card" href="https://plugins.getkirby.com/acme/foo">
  <span class="plugin-card-title">Foo</span>
  <span class="plugin-card-subtitle">Subtitle</span>
</a>
</li>
</ul>
HTML;

    $tools = new OnlinePluginsToolsStub($searchHtml, [], true);

    $payload = $tools->search('Foo', fetch: 1);

    expect($payload['documents'])->toHaveCount(1);
    expect($payload['documents'][0]['markdown'])->toBeNull();
    expect($payload['documents'][0]['truncated'])->toBeFalse();
    expect($payload['documents'][0]['error'])->toBe('boom');
});

it('clamps inputs and validates params', function (): void {
    $tools = new OnlinePluginsToolsStub('<ul class="plugin-cards"></ul>');

    $payload = $tools->search('Foo', kirbyMajorVersion: 500, pricing: '???', sort: '???', limit: 500, fetch: 99, maxChars: -5);

    expect($payload['kirbyMajorVersion'])->toBe('99');
    expect($payload['pricing'])->toBe('');
    expect($payload['sort'])->toBe('');
    expect($payload['fetch'])->toBe(10);
    expect($payload['maxChars'])->toBe(0);
});

it('rejects empty queries', function (): void {
    $tools = new OnlinePluginsToolsStub('<ul class="plugin-cards"></ul>');

    expect(fn () => $tools->search(''))->toThrow(ToolCallException::class, 'Query must not be empty.');
});
