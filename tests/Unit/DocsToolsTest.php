<?php

declare(strict_types=1);

use Bnomei\KirbyMcp\Mcp\Tools\DocsTools;
use Mcp\Exception\ToolCallException;

class DocsToolsStub extends DocsTools
{
    public int $searchCalls = 0;
    public int $markdownCalls = 0;

    /**
     * @param array<string, mixed> $searchPayload
     */
    public function __construct(
        private array $searchPayload,
        private string $markdownBody = '',
        private bool $throwOnMarkdown = false,
    ) {
    }

    protected function httpGet(string $url, string $accept = 'application/json'): string
    {
        if (str_contains($url, 'search.json')) {
            $this->searchCalls++;
            return json_encode($this->searchPayload, JSON_UNESCAPED_SLASHES);
        }

        if (str_ends_with($url, '.md')) {
            $this->markdownCalls++;
            if ($this->throwOnMarkdown) {
                throw new RuntimeException('boom');
            }
            return $this->markdownBody;
        }

        throw new RuntimeException('Unexpected URL');
    }
}

class DocsToolsInvalidJsonStub extends DocsTools
{
    protected function httpGet(string $url, string $accept = 'application/json'): string
    {
        return 'not-json';
    }
}

it('searches Kirby docs and fetches markdown with truncation', function (): void {
    $searchPayload = [
        'pagination' => [
            'page' => 2,
            'firstPage' => 1,
            'lastPage' => 3,
            'pages' => 3,
            'offset' => 20,
            'limit' => 10,
            'total' => 25,
            'start' => 21,
            'end' => 30,
        ],
        'results' => [
            'data' => [
                [
                    'area' => 'docs',
                    'title' => 'Kirby &amp; CMS',
                    'intro' => "Hello\nWorld",
                    'byline' => "By\nme",
                    'objectID' => 'docs/guide/intro',
                ],
                [
                    'area' => 'cookbook',
                    'title' => 'Recipe',
                    'intro' => '',
                    'byline' => '',
                    'objectID' => 'cookbook/recipe',
                ],
                [
                    'area' => 'docs',
                    'title' => 'Skipped',
                    'objectID' => '',
                ],
            ],
        ],
    ];

    $tools = new DocsToolsStub($searchPayload, '1234567890');
    $payload = $tools->search(' Kirby ', ' docs ', limit: 2, fetch: 1, maxChars: 5);

    expect($payload['query'])->toBe('Kirby');
    expect($payload['area'])->toBe('docs');
    expect($payload['fetch'])->toBe(1);
    expect($payload['maxChars'])->toBe(5);
    expect($payload['pagination']['page'])->toBe(2);
    expect($payload['pagination']['total'])->toBe(25);

    $results = $payload['results'];
    expect($results)->toHaveCount(2);
    expect($results[0]['title'])->toBe('Kirby & CMS');
    expect($results[0]['intro'])->toBe('Hello World');
    expect($results[0]['byline'])->toBe('By me');
    expect($results[0]['markdownUrl'])->toBe('https://getkirby.com/docs/guide/intro.md');
    expect($results[1]['markdownUrl'])->toBeNull();

    $documents = $payload['documents'];
    expect($documents)->toHaveCount(1);
    expect($documents[0]['markdown'])->toBe('12345');
    expect($documents[0]['truncated'])->toBeTrue();
    expect($documents[0]['error'])->toBeNull();
    expect($payload['document'])->toBe($documents[0]);

    expect($tools->searchCalls)->toBe(1);
    expect($tools->markdownCalls)->toBe(1);
});

it('records markdown fetch errors without throwing', function (): void {
    $searchPayload = [
        'results' => [
            'data' => [
                [
                    'area' => 'docs',
                    'title' => 'Kirby',
                    'intro' => '',
                    'byline' => '',
                    'objectID' => 'docs/guide/intro',
                ],
            ],
        ],
    ];

    $tools = new DocsToolsStub($searchPayload, '', true);
    $payload = $tools->search('Kirby', fetch: 1);

    expect($payload['documents'])->toHaveCount(1);
    expect($payload['documents'][0]['markdown'])->toBeNull();
    expect($payload['documents'][0]['truncated'])->toBeFalse();
    expect($payload['documents'][0]['error'])->toBe('boom');
});

it('clamps inputs and defaults empty area to all', function (): void {
    $searchPayload = [
        'results' => [
            'data' => [],
        ],
    ];

    $tools = new DocsToolsStub($searchPayload);
    $payload = $tools->search('Kirby', '   ', limit: 500, fetch: 99, maxChars: -5);

    expect($payload['area'])->toBe('all');
    expect($payload['fetch'])->toBe(10);
    expect($payload['maxChars'])->toBe(0);
    expect($payload['sourceUrl'])->toContain('limit=50');
});

it('rejects empty queries', function (): void {
    $tools = new DocsToolsStub(['results' => ['data' => []]]);

    expect(fn () => $tools->search(''))->toThrow(ToolCallException::class, 'Query must not be empty.');
});

it('throws when search JSON cannot be parsed', function (): void {
    $tools = new DocsToolsInvalidJsonStub();

    expect(fn () => $tools->search('Kirby'))->toThrow(ToolCallException::class, 'Failed to parse JSON');
});
