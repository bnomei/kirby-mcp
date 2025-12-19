<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\McpLog;
use Bnomei\KirbyMcp\Mcp\Support\KbDocuments;
use Bnomei\KirbyMcp\Support\FuzzySearch;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\ClientGateway;

final class KbTools
{
    /**
     * @return array{
     *   query: string,
     *   normalizedQuery: string,
     *   fetch: int,
     *   maxChars: int,
     *   needles: array<int, string>,
     *   maxDist: int,
     *   kbRoot: string,
     *   filesScanned: int,
     *   matchCount: int,
     *   returnedCount: int,
     *   results: array<int, array{
     *     file: string,
     *     title: string,
     *     score: int,
     *     matchedNeedles: array<int, string>,
     *     preview: string
     *   }>,
     *   documents: array<int, array{
     *     file: string,
     *     title: string,
     *     markdown: string|null,
     *     truncated: bool,
     *     error: string|null
     *   }>,
     *   document: array{
     *     file: string,
     *     title: string,
     *     markdown: string|null,
     *     truncated: bool,
     *     error: string|null
     *   }|null
     * }
     */
    #[McpToolIndex(
        whenToUse: 'Use to search the bundled local Kirby knowledge base markdown files (kb/) with fuzzy matching (typos tolerated). Prefer this before kirby_online.',
        keywords: [
            'search' => 100,
            'kb' => 80,
            'knowledge' => 60,
            'kirby' => 40,
            'markdown' => 30,
            'fuzzy' => 40,
            'levenshtein' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_search',
        description: 'Search the bundled local Kirby knowledge base markdown files (kb/) using fuzzy Levenshtein matching and optionally return full markdown for the top matches (fetch). Prefer this before kirby_online.',
        annotations: new ToolAnnotations(
            title: 'Kirby Search',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function search(
        string $query,
        int $limit = 10,
        int $maxDist = 2,
        int $fetch = 1,
        int $maxChars = 20000,
        ?ClientGateway $client = null
    ): array {
        try {
            $normalizedQuery = self::normalizeQuery($query);
            if ($normalizedQuery === '') {
                throw new ToolCallException('Query must not be empty.');
            }

            $needles = array_values(array_unique(explode(' ', $normalizedQuery)));

            $limit = max(1, min(50, $limit));
            $maxDist = max(0, min(10, $maxDist));
            $fetch = max(0, min(10, $fetch));
            $maxChars = max(0, $maxChars);

            $projectRoot = KbDocuments::projectRoot();
            $kbRoot = KbDocuments::kbRoot();

            $results = [];
            $kbDocuments = KbDocuments::all();
            $filesScanned = count($kbDocuments);

            foreach ($kbDocuments as $relative => $contents) {
                if ($contents === '') {
                    continue;
                }

                $matchedNeedles = [];
                foreach ($needles as $needle) {
                    if (FuzzySearch::fuzzySearch($needle, $contents, $maxDist)) {
                        $matchedNeedles[] = $needle;
                    }
                }

                $score = count($matchedNeedles);
                if ($score === 0) {
                    continue;
                }

                $fallbackTitle = basename($relative);
                $title = self::extractTitle($contents, $fallbackTitle);

                $results[] = [
                    'file' => $relative,
                    'title' => $title,
                    'score' => $score,
                    'matchedNeedles' => $matchedNeedles,
                    'preview' => self::preview($contents, 220),
                ];
            }

            usort($results, static function (array $a, array $b): int {
                $byScore = ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
                if ($byScore !== 0) {
                    return $byScore;
                }

                return strcmp((string)($a['file'] ?? ''), (string)($b['file'] ?? ''));
            });

            $matchCount = count($results);
            $results = array_slice($results, 0, $limit);
            $returnedCount = count($results);

            $documents = [];
            if ($fetch > 0) {
                $remaining = $fetch;

                foreach ($results as $result) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $relativeFile = $result['file'] ?? null;
                    $title = $result['title'] ?? null;

                    if (!is_string($relativeFile) || $relativeFile === '') {
                        continue;
                    }

                    $markdown = null;
                    $truncated = false;
                    $error = null;

                    try {
                        $contents = $kbDocuments[$relativeFile] ?? null;
                        if (!is_string($contents) || $contents === '') {
                            $absolutePath = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeFile);
                            if (!is_file($absolutePath)) {
                                throw new \RuntimeException('File not found: ' . $relativeFile);
                            }

                            $contents = file_get_contents($absolutePath);
                            if (!is_string($contents)) {
                                throw new \RuntimeException('Failed to read file: ' . $relativeFile);
                            }
                        }

                        $markdown = $contents;
                        if ($maxChars > 0 && strlen($markdown) > $maxChars) {
                            $markdown = substr($markdown, 0, $maxChars);
                            $truncated = true;
                        }
                    } catch (\Throwable $exception) {
                        $error = $exception->getMessage();
                    }

                    $documents[] = [
                        'file' => $relativeFile,
                        'title' => is_string($title) ? $title : basename($relativeFile),
                        'markdown' => $markdown,
                        'truncated' => $truncated,
                        'error' => $error,
                    ];

                    $remaining--;
                }
            }

            return [
                'query' => $query,
                'normalizedQuery' => $normalizedQuery,
                'fetch' => $fetch,
                'maxChars' => $maxChars,
                'needles' => $needles,
                'maxDist' => $maxDist,
                'kbRoot' => $kbRoot,
                'filesScanned' => $filesScanned,
                'matchCount' => $matchCount,
                'returnedCount' => $returnedCount,
                'results' => $results,
                'documents' => $documents,
                'document' => $documents[0] ?? null,
            ];
        } catch (ToolCallException $exception) {
            McpLog::error($client, [
                'tool' => 'kirby_search',
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        } catch (\Throwable $exception) {
            McpLog::error($client, [
                'tool' => 'kirby_search',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }

    private static function normalizeQuery(string $query): string
    {
        $query = trim($query);
        if ($query === '') {
            return '';
        }

        $query = str_replace([',', ';'], ' ', $query);
        $query = preg_replace('/[^\\p{L}\\p{N}]/u', ' ', $query) ?? $query;
        $query = preg_replace('/\\s+/u', ' ', $query) ?? $query;

        return trim(mb_strtolower($query));
    }

    private static function extractTitle(string $markdown, string $fallback): string
    {
        if (preg_match('/^#[ \\t]+(.+)$/m', $markdown, $matches) === 1) {
            $title = $matches[1] ?? '';
            $title = is_string($title) ? trim($title) : '';
            if ($title !== '') {
                return $title;
            }
        }

        return $fallback;
    }

    private static function preview(string $markdown, int $maxChars = 220): string
    {
        $text = preg_replace('/\\s+/u', ' ', $markdown) ?? $markdown;
        $text = trim($text);

        if ($maxChars > 0 && strlen($text) > $maxChars) {
            return substr($text, 0, $maxChars);
        }

        return $text;
    }
}
