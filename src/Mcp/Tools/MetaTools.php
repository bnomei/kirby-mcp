<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\ToolIndex;
use Bnomei\KirbyMcp\Mcp\SessionState;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\ToolAnnotations;

final class MetaTools
{
    /**
     * Suggest which Kirby MCP tool(s) to call for a task.
     *
     * Provide either a free-form query or explicit keywords; the tool returns a ranked list with a score and a short “when to use”.
     *
     * @param array<int, mixed> $keywords
     * @return array{
     *   query: string|null,
     *   keywords: array<int, string>,
     *   initRecommended: bool,
     *   suggestions: array<int, array{tool: string, name: string, kind: string, score: int, title: string, whenToUse: string, matched: array<int, string>}>
     * }
     */
    #[McpToolIndex(
        whenToUse: 'Use when you are unsure which Kirby MCP tool to call next; returns a ranked list based on weighted keywords.',
        keywords: [
            'suggest' => 100,
            'tools' => 40,
            'tool' => 40,
            'which' => 20,
            'recommend' => 40,
            'next' => 20,
        ],
    )]
    #[McpTool(
        name: 'kirby_tool_suggest',
        description: 'Suggest the best next Kirby MCP tool/resource for a task using a weighted keyword matcher. Suggestions can include tools, resources (`kirby://...`), and resource templates (`kirby://.../{param}`). Use this when you are unsure what to call/read next. Resource: `kirby://tools`.',
        annotations: new ToolAnnotations(
            title: 'Suggest Tools & Resources',
            readOnlyHint: true,
            openWorldHint: false,
        ),
    )]
    public function suggestTools(?string $query = null, array $keywords = [], int $limit = 8): array
    {
        $keywordsFromQuery = is_string($query) && trim($query) !== ''
            ? $this->extractKeywordsFromQuery($query)
            : [];

        $normalized = $this->normalizeKeywords(array_merge($keywords, $keywordsFromQuery));

        $scored = [];
        foreach (ToolIndex::all() as $tool) {
            $score = 0;
            $matched = [];

            foreach ($normalized as $keyword) {
                $weight = $tool['keywords'][$keyword] ?? null;
                if (is_int($weight)) {
                    $score += $weight;
                    $matched[] = $keyword;
                    continue;
                }

                // Small fuzzy boost: if a keyword is a substring of a known keyword or tool name.
                foreach ($tool['keywords'] as $k => $w) {
                    if (str_contains($k, $keyword) || str_contains($keyword, $k)) {
                        $score += max(1, (int) floor($w / 4));
                        $matched[] = $keyword;
                        break;
                    }
                }

                if (str_contains($tool['name'], $keyword)) {
                    $score += 1;
                    $matched[] = $keyword;
                }
            }

            $matched = array_values(array_unique($matched));

            $scored[] = [
                'tool' => $tool['name'],
                'name' => $tool['name'],
                'kind' => $tool['kind'],
                'score' => $score,
                'title' => $tool['title'],
                'whenToUse' => $tool['whenToUse'],
                'matched' => $matched,
            ];
        }

        usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        $limit = max(1, min(25, $limit));
        $scored = array_slice($scored, 0, $limit);

        return [
            'query' => $query,
            'keywords' => $normalized,
            'initRecommended' => !SessionState::initCalled(),
            'suggestions' => $scored,
        ];
    }

    /**
     * @param array<int, mixed> $keywords
     * @return array<int, string>
     */
    private function normalizeKeywords(array $keywords): array
    {
        $normalized = [];
        foreach ($keywords as $keyword) {
            if (!is_string($keyword)) {
                continue;
            }

            $keyword = trim(strtolower($keyword));
            if ($keyword === '') {
                continue;
            }

            $keyword = preg_replace('/[^a-z0-9\\-_:]+/u', ' ', $keyword) ?? $keyword;
            $keyword = trim(preg_replace('/\\s+/u', ' ', $keyword) ?? $keyword);
            if ($keyword === '') {
                continue;
            }

            foreach (explode(' ', $keyword) as $part) {
                $part = trim($part);
                if ($part === '' || strlen($part) < 2) {
                    continue;
                }
                $normalized[] = $part;
            }
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }

    /**
     * @return array<int, string>
     */
    private function extractKeywordsFromQuery(string $query): array
    {
        $query = trim(strtolower($query));
        if ($query === '') {
            return [];
        }

        $query = preg_replace('/[^a-z0-9\\-_:]+/u', ' ', $query) ?? $query;
        $query = trim(preg_replace('/\\s+/u', ' ', $query) ?? $query);

        if ($query === '') {
            return [];
        }

        return explode(' ', $query);
    }
}
