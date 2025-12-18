<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Docs\KirbyDocsUrl;
use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\McpLog;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;

final class DocsTools
{
    /**
     * Search the official Kirby site index (Guide/Reference/Cookbook/Kosmos/Plugins) via `getkirby.com/search.json`.
     *
     * Note: For any official docs result (`docs/...`), this tool returns `crawlUrl` with `.md` appended
     * so agents can fetch markdown directly.
     *
     * @return array{
     *   query: string,
     *   area: string,
     *   sourceUrl: string,
     *   pagination: array{page:int, firstPage:int, lastPage:int, pages:int, offset:int, limit:int, total:int, start:int, end:int}|null,
     *   results: array<int, array{
     *     area: string,
     *     title: string,
     *     intro: string,
     *     byline: string,
     *     objectId: string,
     *     path: string,
     *     htmlUrl: string,
     *     crawlUrl: string,
     *     markdownUrl: string|null
     *   }>
     * }
     */
    #[McpToolIndex(
        whenToUse: 'Use when you need official Kirby docs links (Guide/Reference/Cookbook/Kosmos/Plugins) for a topic; returns crawl-friendly `.md` URLs for docs pages.',
        keywords: [
            'docs' => 100,
            'documentation' => 90,
            'search' => 80,
            'reference' => 70,
            'cookbook' => 70,
            'guide' => 70,
            'quicktips' => 60,
            'plugins' => 40,
            'getkirby' => 40,
        ],
    )]
    #[McpTool(
        name: 'kirby_docs_search',
        description: 'Search official Kirby docs via `getkirby.com/search.json`. Returns titles/intros/URLs; for docs pages it returns crawl-friendly `.md` URLs.',
        annotations: new ToolAnnotations(
            title: 'Kirby Docs Search',
            readOnlyHint: true,
            openWorldHint: true,
        ),
    )]
    public function search(string $query, string $area = 'all', int $limit = 10, ?ClientGateway $client = null): array
    {
        try {
            $query = trim($query);
            if ($query === '') {
                throw new ToolCallException('Query must not be empty.');
            }

            $area = trim($area);
            if ($area === '') {
                $area = 'all';
            }

            $limit = max(1, min(50, $limit));

            $url = 'https://getkirby.com/search.json?q=' . rawurlencode($query)
                . '&area=' . rawurlencode($area)
                . '&limit=' . rawurlencode((string)$limit);

            $data = $this->getJson($url);

            $pagination = null;
            if (isset($data['pagination']) && is_array($data['pagination'])) {
                $pagination = $this->normalizePagination($data['pagination']);
            }

            $results = [];
            $rows = $data['results']['data'] ?? null;
            if (is_array($rows)) {
                foreach ($rows as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $objectId = $row['objectID'] ?? null;
                    if (!is_string($objectId) || $objectId === '') {
                        continue;
                    }

                    $urls = KirbyDocsUrl::fromObjectId($objectId);

                    $results[] = [
                        'area' => $this->decodeText($row['area'] ?? ''),
                        'title' => $this->decodeText($row['title'] ?? ''),
                        'intro' => $this->decodeText($row['intro'] ?? ''),
                        'byline' => $this->decodeText($row['byline'] ?? ''),
                        'objectId' => $objectId,
                        'path' => $urls['path'],
                        'htmlUrl' => $urls['htmlUrl'],
                        'crawlUrl' => $urls['crawlUrl'],
                        'markdownUrl' => $urls['markdownUrl'],
                    ];
                }
            }

            return [
                'query' => $query,
                'area' => $area,
                'sourceUrl' => $url,
                'pagination' => $pagination,
                'results' => $results,
            ];
        } catch (ToolCallException $exception) {
            McpLog::error($client, [
                'tool' => 'kirby_docs_search',
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        } catch (\Throwable $exception) {
            McpLog::error($client, [
                'tool' => 'kirby_docs_search',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getJson(string $url): array
    {
        $body = $this->httpGet($url);

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('Failed to parse JSON from ' . $url . ': ' . $exception->getMessage(), 0, $exception);
        }

        return $decoded;
    }

    private function httpGet(string $url): string
    {
        $userAgent = 'kirby-mcp (MCP server)';

        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            if ($handle === false) {
                throw new \RuntimeException('Failed to initialize cURL.');
            }

            curl_setopt_array($handle, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_USERAGENT => $userAgent,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                ],
            ]);

            $response = curl_exec($handle);
            if (!is_string($response)) {
                $error = curl_error($handle);
                curl_close($handle);
                throw new \RuntimeException('HTTP request failed: ' . ($error !== '' ? $error : 'unknown error'));
            }

            $status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            curl_close($handle);

            if ($status < 200 || $status >= 300) {
                throw new \RuntimeException('HTTP request failed with status ' . $status);
            }

            return $response;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 20,
                'header' => implode("\r\n", [
                    'User-Agent: ' . $userAgent,
                    'Accept: application/json',
                ]),
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if (!is_string($response)) {
            throw new \RuntimeException('HTTP request failed: file_get_contents returned false');
        }

        return $response;
    }

    private function decodeText(mixed $value): string
    {
        if (!is_string($value)) {
            return '';
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * @param array<mixed> $pagination
     * @return array{page:int, firstPage:int, lastPage:int, pages:int, offset:int, limit:int, total:int, start:int, end:int}
     */
    private function normalizePagination(array $pagination): array
    {
        return [
            'page' => (int)($pagination['page'] ?? 1),
            'firstPage' => (int)($pagination['firstPage'] ?? 1),
            'lastPage' => (int)($pagination['lastPage'] ?? 1),
            'pages' => (int)($pagination['pages'] ?? 1),
            'offset' => (int)($pagination['offset'] ?? 0),
            'limit' => (int)($pagination['limit'] ?? 0),
            'total' => (int)($pagination['total'] ?? 0),
            'start' => (int)($pagination['start'] ?? 0),
            'end' => (int)($pagination['end'] ?? 0),
        ];
    }
}
