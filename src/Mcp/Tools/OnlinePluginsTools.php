<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\McpLog;
use Bnomei\KirbyMcp\Mcp\Tools\Concerns\StructuredToolResult;
use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;

class OnlinePluginsTools
{
    use StructuredToolResult;

    /**
     * Search the official Kirby plugins directory.
     *
     * @return array{
     *   query: string,
     *   kirbyMajorVersion: string,
     *   pricing: string,
     *   sort: string,
     *   limit: int,
     *   fetch: int,
     *   maxChars: int,
     *   sourceUrl: string,
     *   results: array<int, array{
     *     title: string,
     *     subtitle: string,
     *     slug: string,
     *     url: string,
     *     supportsKirbyVersions: array<int, int>
     *   }>,
     *   documents: array<int, array{
     *     url: string,
     *     slug: string,
     *     title: string,
     *     data: array<string, mixed>|null,
     *     markdown: string|null,
     *     truncated: bool,
     *     error: string|null
     *   }>,
     *   document: array{
     *     url: string,
     *     slug: string,
     *     title: string,
     *     data: array<string, mixed>|null,
     *     markdown: string|null,
     *     truncated: bool,
     *     error: string|null
     *   }|null,
     *   markdown: string
     * }
     */
    #[McpToolIndex(
        whenToUse: 'Use as an online fallback when you need plugin suggestions from the official Kirby plugin directory (plugins.getkirby.com). Prefer kirby_plugins_index to inspect already-installed plugins in the current project.',
        keywords: [
            'plugins' => 100,
            'plugin' => 100,
            'directory' => 70,
            'marketplace' => 50,
            'search' => 40,
            'online' => 60,
            'getkirby' => 40,
            'kirby' => 30,
        ],
    )]
    #[McpTool(
        name: 'kirby_online_plugins',
        description: 'Search the official Kirby plugin directory (plugins.getkirby.com) and optionally fetch individual plugin pages to extract key details and return a markdown summary. Online fallback; prefer kirby_plugins_index for installed plugins.',
        annotations: new ToolAnnotations(
            title: 'Kirby Online Plugins',
            readOnlyHint: true,
            openWorldHint: true,
        ),
    )]
    public function search(
        string $query,
        #[CompletionProvider(values: ['', 3, 4, 5])]
        int|string $kirbyMajorVersion = '',
        #[CompletionProvider(values: ['', 'free', 'paid'])]
        string $pricing = '',
        #[CompletionProvider(values: ['', 'title', 'popularity', 'price'])]
        string $sort = '',
        int $limit = 10,
        int $fetch = 1,
        int $maxChars = 20000,
        ?RequestContext $context = null,
    ): array|CallToolResult {
        try {
            $query = trim($query);
            if ($query === '') {
                throw new ToolCallException('Query must not be empty.');
            }

            if (is_int($kirbyMajorVersion)) {
                $kirbyMajorVersion = (string) $kirbyMajorVersion;
            } else {
                $kirbyMajorVersion = trim($kirbyMajorVersion);
            }

            if ($kirbyMajorVersion !== '') {
                $parsed = filter_var($kirbyMajorVersion, FILTER_VALIDATE_INT);
                if ($parsed === false) {
                    $kirbyMajorVersion = '';
                } else {
                    $kirbyMajorVersion = (string) max(1, min(99, (int) $parsed));
                }
            }

            $pricing = trim(mb_strtolower($pricing));
            if (!in_array($pricing, ['', 'free', 'paid'], true)) {
                $pricing = '';
            }

            $sort = trim(mb_strtolower($sort));
            if (!in_array($sort, ['', 'title', 'popularity', 'price'], true)) {
                $sort = '';
            }

            $limit = max(1, min(50, $limit));
            $fetch = max(0, min(10, $fetch));
            $maxChars = max(0, $maxChars);

            $url = 'https://plugins.getkirby.com/search?query=' . rawurlencode($query)
                . '&version=' . rawurlencode($kirbyMajorVersion)
                . '&pricing=' . rawurlencode($pricing)
                . '&sort=' . rawurlencode($sort);

            $html = $this->httpGet($url, 'text/html');

            $results = array_slice($this->parseSearchResults($html), 0, $limit);

            $documents = [];
            $detailsByUrl = [];

            if ($fetch > 0) {
                $remaining = $fetch;

                foreach ($results as $result) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $pluginUrl = $result['url'] ?? null;
                    if (!is_string($pluginUrl) || trim($pluginUrl) === '') {
                        continue;
                    }

                    $title = $result['title'] ?? '';
                    $slug = $result['slug'] ?? '';

                    $markdown = null;
                    $truncated = false;
                    $error = null;
                    $data = null;

                    try {
                        $pluginHtml = $this->httpGet($pluginUrl, 'text/html');
                        $data = $this->parsePluginPage($pluginHtml, $pluginUrl);
                        $detailsByUrl[$pluginUrl] = $data;

                        $markdown = $this->renderPluginMarkdown($data);

                        if ($maxChars > 0 && strlen($markdown) > $maxChars) {
                            $markdown = substr($markdown, 0, $maxChars);
                            $truncated = true;
                        }
                    } catch (\Throwable $exception) {
                        $error = $exception->getMessage();
                    }

                    $documents[] = [
                        'url' => $pluginUrl,
                        'slug' => is_string($slug) ? $slug : '',
                        'title' => is_string($title) ? $title : '',
                        'data' => $data,
                        'markdown' => $markdown,
                        'truncated' => $truncated,
                        'error' => $error,
                    ];

                    $remaining--;
                }
            }

            $summaryMarkdown = $this->renderSearchMarkdown($query, $results, $detailsByUrl);
            if ($maxChars > 0 && strlen($summaryMarkdown) > $maxChars) {
                $summaryMarkdown = substr($summaryMarkdown, 0, $maxChars);
            }

            return $this->maybeStructuredResult($context, [
                'query' => $query,
                'kirbyMajorVersion' => $kirbyMajorVersion,
                'pricing' => $pricing,
                'sort' => $sort,
                'limit' => $limit,
                'fetch' => $fetch,
                'maxChars' => $maxChars,
                'sourceUrl' => $url,
                'results' => $results,
                'documents' => $documents,
                'document' => $documents[0] ?? null,
                'markdown' => $summaryMarkdown,
            ]);
        } catch (ToolCallException $exception) {
            McpLog::error($context, [
                'tool' => 'kirby_online_plugins',
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        } catch (\Throwable $exception) {
            McpLog::error($context, [
                'tool' => 'kirby_online_plugins',
                'error' => $exception->getMessage(),
                'exception' => $exception::class,
            ]);
            throw new ToolCallException($exception->getMessage());
        }
    }

    /**
     * @return array<int, array{title:string, subtitle:string, slug:string, url:string, supportsKirbyVersions:array<int, int>}>
     */
    private function parseSearchResults(string $html): array
    {
        $results = [];

        if (
            preg_match_all(
                '/<a\s+class="plugin-card"\s+href="([^"]+)"[^>]*>(.*?)<\/a>/si',
                $html,
                $matches,
                PREG_SET_ORDER,
            ) !== false
        ) {
            foreach ($matches as $match) {
                $url = $match[1] ?? null;
                $body = $match[2] ?? null;

                if (!is_string($url) || trim($url) === '' || !is_string($body)) {
                    continue;
                }

                $title = $this->matchText('/plugin-card-title">(.*?)<\/span>/si', $body);
                $subtitle = $this->matchText('/plugin-card-subtitle">(.*?)<\/span>/si', $body);

                if ($title === '') {
                    continue;
                }

                $supports = [];
                if (preg_match_all('/data-version="(\d+)"/i', $body, $verMatches) !== false) {
                    foreach ($verMatches[1] ?? [] as $version) {
                        $supports[] = (int) $version;
                    }
                }
                $supports = array_values(array_unique(array_filter($supports, static fn (int $v): bool => $v > 0)));
                sort($supports);

                $results[] = [
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'slug' => $this->slugFromUrl($url),
                    'url' => $url,
                    'supportsKirbyVersions' => $supports,
                ];
            }
        }

        return $results;
    }

    /**
     * @return array{
     *   url: string,
     *   slug: string,
     *   title: string,
     *   subtitle: string,
     *   features: array<int, array{title:string, description:string}>,
     *   ctas: array<int, array{label:string, url:string}>,
     *   meta: array{
     *     version: array{value:string, url:string|null}|null,
     *     license: array{value:string, url:string|null}|null,
     *     stars: int|null,
     *     supportsKirbyVersions: array<int, int>,
     *     created: array{value:string, url:string|null}|null,
     *     updated: array{value:string, url:string|null}|null
     *   },
     *   topics: array<int, array{label:string, url:string}>,
     *   support: array<int, array{label:string, url:string}>,
     *   latestReleases: array<int, array{label:string, url:string}>
     * }
     */
    private function parsePluginPage(string $html, string $url): array
    {
        $title = $this->matchText('/<h1\s+class="plugin-title"[^>]*>(.*?)<\/h1>/si', $html);
        $subtitle = $this->matchText('/<div\s+class="plugin-subtitle[^\"]*"[^>]*>(.*?)<\/div>/si', $html);

        $meta = $this->parsePluginMeta($html);
        $features = $this->parsePluginFeatures($html);
        $ctas = $this->parsePluginCtas($html);
        $topics = $this->parsePluginListSection($html, 'Topics');
        $support = $this->parsePluginListSection($html, 'Support');
        $latestReleases = $this->parsePluginListSection($html, 'Latest releases');

        return [
            'url' => $url,
            'slug' => $this->slugFromUrl($url),
            'title' => $title,
            'subtitle' => $subtitle,
            'features' => $features,
            'ctas' => $ctas,
            'meta' => $meta,
            'topics' => $topics,
            'support' => $support,
            'latestReleases' => $latestReleases,
        ];
    }

    /**
     * @return array<int, array{label:string, url:string}>
     */
    private function parsePluginCtas(string $html): array
    {
        $ctas = [];

        foreach (['plugin-cta-links', 'plugin-cta-actions'] as $class) {
            if (preg_match('/<div class="' . preg_quote($class, '/') . '"[^>]*>(.*?)<\/div>/si', $html, $match) !== 1) {
                continue;
            }

            $section = $match[1] ?? '';
            if (!is_string($section) || $section === '') {
                continue;
            }

            if (preg_match_all('/<a\s+class="button"[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/si', $section, $links, PREG_SET_ORDER) === false) {
                continue;
            }

            foreach ($links as $link) {
                $href = $link[1] ?? null;
                $labelHtml = $link[2] ?? null;

                if (!is_string($href) || trim($href) === '' || !is_string($labelHtml)) {
                    continue;
                }

                $label = $this->decodeText($this->stripTags($labelHtml));
                if ($label === '') {
                    continue;
                }

                $ctas[] = [
                    'label' => $label,
                    'url' => html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                ];
            }
        }

        $unique = [];
        foreach ($ctas as $cta) {
            $key = ($cta['label'] ?? '') . '|' . ($cta['url'] ?? '');
            $unique[$key] = $cta;
        }

        return array_values($unique);
    }

    /**
     * @return array<int, array{title:string, description:string}>
     */
    private function parsePluginFeatures(string $html): array
    {
        if (preg_match('/<section class="plugin-features"[^>]*>(.*?)<\/section>/si', $html, $match) !== 1) {
            return [];
        }

        $section = $match[1] ?? '';
        if (!is_string($section) || $section === '') {
            return [];
        }

        $features = [];

        if (preg_match_all('/<div class="plugin-feature"[^>]*>\s*<dt>(.*?)<\/dt>\s*<dd>(.*?)<\/dd>\s*<\/div>/si', $section, $rows, PREG_SET_ORDER) === false) {
            return [];
        }

        foreach ($rows as $row) {
            $title = $this->decodeText($this->stripTags($row[1] ?? ''));
            $description = $this->decodeText($this->stripTags($row[2] ?? ''));

            if ($title === '' && $description === '') {
                continue;
            }

            $features[] = [
                'title' => $title,
                'description' => $description,
            ];
        }

        return $features;
    }

    /**
     * @return array{
     *   version: array{value:string, url:string|null}|null,
     *   license: array{value:string, url:string|null}|null,
     *   stars: int|null,
     *   supportsKirbyVersions: array<int, int>,
     *   created: array{value:string, url:string|null}|null,
     *   updated: array{value:string, url:string|null}|null
     * }
     */
    private function parsePluginMeta(string $html): array
    {
        $meta = [
            'version' => null,
            'license' => null,
            'stars' => null,
            'supportsKirbyVersions' => [],
            'created' => null,
            'updated' => null,
        ];

        if (preg_match('/<section class="section plugin-meta"[^>]*>(.*?)<\/section>/si', $html, $match) !== 1) {
            return $meta;
        }

        $section = $match[1] ?? '';
        if (!is_string($section) || $section === '') {
            return $meta;
        }

        if (preg_match_all('/<div>\s*<dt[^>]*>(.*?)<\/dt>\s*<dd[^>]*>(.*?)<\/dd>\s*<\/div>/si', $section, $rows, PREG_SET_ORDER) === false) {
            return $meta;
        }

        foreach ($rows as $row) {
            $dtHtml = $row[1] ?? '';
            $ddHtml = $row[2] ?? '';

            if (!is_string($dtHtml) || !is_string($ddHtml)) {
                continue;
            }

            $label = $this->decodeText($this->stripTags($dtHtml));
            $text = $this->decodeText($this->stripTags($ddHtml));
            $href = null;
            if (preg_match('/href="([^"]+)"/i', $ddHtml, $hrefMatch) === 1) {
                $href = html_entity_decode((string) ($hrefMatch[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            if ($label === 'Version') {
                $meta['version'] = ['value' => $text, 'url' => $href];
            } elseif ($label === 'License') {
                $meta['license'] = ['value' => $text, 'url' => $href];
            } elseif ($label === 'Stars') {
                $parsed = filter_var($text, FILTER_VALIDATE_INT);
                $meta['stars'] = $parsed !== false ? (int) $parsed : null;
            } elseif ($label === 'Supports') {
                $supports = [];
                if (preg_match_all('/data-version="(\d+)"/i', $ddHtml, $verMatches) !== false) {
                    foreach ($verMatches[1] ?? [] as $version) {
                        $supports[] = (int) $version;
                    }
                }
                $supports = array_values(array_unique(array_filter($supports, static fn (int $v): bool => $v > 0)));
                sort($supports);
                $meta['supportsKirbyVersions'] = $supports;
            } elseif ($label === 'Created') {
                $meta['created'] = ['value' => $text, 'url' => $href];
            } elseif ($label === 'Updated') {
                $meta['updated'] = ['value' => $text, 'url' => $href];
            }
        }

        return $meta;
    }

    /**
     * @return array<int, array{label:string, url:string}>
     */
    private function parsePluginListSection(string $html, string $dtLabel): array
    {
        if (preg_match('/<section class="section plugin-info"[^>]*>(.*?)<\/section>/si', $html, $match) !== 1) {
            return [];
        }

        $section = $match[1] ?? '';
        if (!is_string($section) || $section === '') {
            return [];
        }

        $pattern = '/<dt>\s*' . preg_quote($dtLabel, '/') . '\s*<\/dt>\s*<dd[^>]*>(.*?)<\/dd>/si';
        if (preg_match($pattern, $section, $dtMatch) !== 1) {
            return [];
        }

        $ddHtml = $dtMatch[1] ?? '';
        if (!is_string($ddHtml) || $ddHtml === '') {
            return [];
        }

        $items = [];

        if (preg_match_all('/<a[^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/si', $ddHtml, $links, PREG_SET_ORDER) === false) {
            return [];
        }

        foreach ($links as $link) {
            $href = $link[1] ?? null;
            $labelHtml = $link[2] ?? null;

            if (!is_string($href) || trim($href) === '' || !is_string($labelHtml)) {
                continue;
            }

            $label = $this->decodeText($this->stripTags($labelHtml));
            if ($label === '') {
                continue;
            }

            $items[] = [
                'label' => $label,
                'url' => html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            ];
        }

        $unique = [];
        foreach ($items as $item) {
            $key = ($item['label'] ?? '') . '|' . ($item['url'] ?? '');
            $unique[$key] = $item;
        }

        return array_values($unique);
    }

    /**
     * @param array<int, array{title:string, subtitle:string, slug:string, url:string, supportsKirbyVersions:array<int, int>}> $results
     * @param array<string, array<string, mixed>> $detailsByUrl
     */
    private function renderSearchMarkdown(string $query, array $results, array $detailsByUrl): string
    {
        $lines = [];
        $lines[] = '# Kirby plugin search results';
        $lines[] = '';
        $lines[] = 'Query: `' . $query . '`';
        $lines[] = '';

        if ($results === []) {
            $lines[] = '_No plugins found._';
            return implode("\n", $lines) . "\n";
        }

        foreach ($results as $result) {
            $url = $result['url'];
            $title = $result['title'];
            $subtitle = $result['subtitle'] !== '' ? ' — ' . $result['subtitle'] : '';

            $extras = [];

            $supports = $result['supportsKirbyVersions'] ?? [];
            if (is_array($supports) && $supports !== []) {
                $extras[] = 'K' . implode(',K', $supports);
            }

            $details = $detailsByUrl[$url] ?? null;
            if (is_array($details)) {
                $meta = $details['meta'] ?? null;
                if (is_array($meta)) {
                    $version = $meta['version']['value'] ?? null;
                    if (is_string($version) && $version !== '') {
                        $extras[] = 'v' . $version;
                    }

                    $stars = $meta['stars'] ?? null;
                    if (is_int($stars)) {
                        $extras[] = '★ ' . $stars;
                    }
                }
            }

            $suffix = $extras !== [] ? ' (' . implode(', ', $extras) . ')' : '';

            $lines[] = '- [' . $title . '](' . $url . ')' . $subtitle . $suffix;
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param array{
     *   url: string,
     *   slug: string,
     *   title: string,
     *   subtitle: string,
     *   features: array<int, array{title:string, description:string}>,
     *   ctas: array<int, array{label:string, url:string}>,
     *   meta: array{
     *     version: array{value:string, url:string|null}|null,
     *     license: array{value:string, url:string|null}|null,
     *     stars: int|null,
     *     supportsKirbyVersions: array<int, int>,
     *     created: array{value:string, url:string|null}|null,
     *     updated: array{value:string, url:string|null}|null
     *   },
     *   topics: array<int, array{label:string, url:string}>,
     *   support: array<int, array{label:string, url:string}>,
     *   latestReleases: array<int, array{label:string, url:string}>
     * } $plugin
     */
    private function renderPluginMarkdown(array $plugin): string
    {
        $lines = [];

        $title = $plugin['title'] ?? '';
        $subtitle = $plugin['subtitle'] ?? '';
        $url = $plugin['url'] ?? '';

        $lines[] = '# ' . ($title !== '' ? $title : 'Kirby plugin');
        if (is_string($subtitle) && $subtitle !== '') {
            $lines[] = '';
            $lines[] = $subtitle;
        }

        $lines[] = '';
        $lines[] = '- URL: ' . $url;

        $meta = $plugin['meta'] ?? [];

        $supports = $meta['supportsKirbyVersions'] ?? [];
        if (is_array($supports) && $supports !== []) {
            $lines[] = '- Supports: Kirby ' . implode(', ', $supports);
        }

        $versionValue = $meta['version']['value'] ?? null;
        $versionUrl = $meta['version']['url'] ?? null;
        if (is_string($versionValue) && $versionValue !== '') {
            $lines[] = '- Version: ' . (is_string($versionUrl) && $versionUrl !== ''
                ? '[' . $versionValue . '](' . $versionUrl . ')'
                : $versionValue);
        }

        $licenseValue = $meta['license']['value'] ?? null;
        $licenseUrl = $meta['license']['url'] ?? null;
        if (is_string($licenseValue) && $licenseValue !== '') {
            $lines[] = '- License: ' . (is_string($licenseUrl) && $licenseUrl !== ''
                ? '[' . $licenseValue . '](' . $licenseUrl . ')'
                : $licenseValue);
        }

        $stars = $meta['stars'] ?? null;
        if (is_int($stars)) {
            $lines[] = '- Stars: ' . $stars;
        }

        $createdValue = $meta['created']['value'] ?? null;
        if (is_string($createdValue) && $createdValue !== '') {
            $lines[] = '- Created: ' . $createdValue;
        }

        $updatedValue = $meta['updated']['value'] ?? null;
        if (is_string($updatedValue) && $updatedValue !== '') {
            $lines[] = '- Updated: ' . $updatedValue;
        }

        $ctas = $plugin['ctas'] ?? [];
        if (is_array($ctas) && $ctas !== []) {
            $lines[] = '';
            $lines[] = '## Links';
            foreach ($ctas as $cta) {
                if (!is_array($cta)) {
                    continue;
                }
                $label = $cta['label'] ?? '';
                $href = $cta['url'] ?? '';
                if (is_string($label) && $label !== '' && is_string($href) && $href !== '') {
                    $lines[] = '- [' . $label . '](' . $href . ')';
                }
            }
        }

        $topics = $plugin['topics'] ?? [];
        if (is_array($topics) && $topics !== []) {
            $lines[] = '';
            $lines[] = '## Topics';
            foreach ($topics as $topic) {
                if (!is_array($topic)) {
                    continue;
                }
                $label = $topic['label'] ?? '';
                $href = $topic['url'] ?? '';
                if (is_string($label) && $label !== '' && is_string($href) && $href !== '') {
                    $lines[] = '- [' . $label . '](' . $href . ')';
                }
            }
        }

        $support = $plugin['support'] ?? [];
        if (is_array($support) && $support !== []) {
            $lines[] = '';
            $lines[] = '## Support';
            foreach ($support as $entry) {
                if (!is_array($entry)) {
                    continue;
                }
                $label = $entry['label'] ?? '';
                $href = $entry['url'] ?? '';
                if (is_string($label) && $label !== '' && is_string($href) && $href !== '') {
                    $lines[] = '- [' . $label . '](' . $href . ')';
                }
            }
        }

        $releases = $plugin['latestReleases'] ?? [];
        if (is_array($releases) && $releases !== []) {
            $lines[] = '';
            $lines[] = '## Latest releases';
            foreach ($releases as $release) {
                if (!is_array($release)) {
                    continue;
                }
                $label = $release['label'] ?? '';
                $href = $release['url'] ?? '';
                if (is_string($label) && $label !== '' && is_string($href) && $href !== '') {
                    $lines[] = '- [' . $label . '](' . $href . ')';
                }
            }
        }

        $features = $plugin['features'] ?? [];
        if (is_array($features) && $features !== []) {
            $lines[] = '';
            $lines[] = '## Features';
            foreach ($features as $feature) {
                if (!is_array($feature)) {
                    continue;
                }

                $featureTitle = $feature['title'] ?? '';
                $description = $feature['description'] ?? '';
                if (is_string($featureTitle) && $featureTitle !== '') {
                    $lines[] = '- **' . $featureTitle . '**' . (is_string($description) && $description !== '' ? ' — ' . $description : '');
                } elseif (is_string($description) && $description !== '') {
                    $lines[] = '- ' . $description;
                }
            }
        }

        return implode("\n", $lines) . "\n";
    }

    protected function httpGet(string $url, string $accept = 'text/html'): string
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
                    'Accept: ' . $accept,
                ],
            ]);

            $response = curl_exec($handle);
            if (!is_string($response)) {
                $error = curl_error($handle);
                if (PHP_VERSION_ID < 80500) {
                    curl_close($handle);
                }
                throw new \RuntimeException('HTTP request failed: ' . ($error !== '' ? $error : 'unknown error'));
            }

            $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            if (PHP_VERSION_ID < 80500) {
                curl_close($handle);
            }

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
                    'Accept: ' . $accept,
                ]),
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if (!is_string($response)) {
            throw new \RuntimeException('HTTP request failed: file_get_contents returned false');
        }

        return $response;
    }

    private function matchText(string $pattern, string $html): string
    {
        if (preg_match($pattern, $html, $match) === 1) {
            $value = $match[1] ?? '';
            if (is_string($value)) {
                return $this->decodeText($this->stripTags($value));
            }
        }

        return '';
    }

    private function stripTags(string $html): string
    {
        return preg_replace('/<[^>]+>/u', ' ', $html) ?? $html;
    }

    private function decodeText(mixed $value): string
    {
        if (!is_string($value)) {
            return '';
        }

        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function slugFromUrl(string $url): string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? '';
        if (!is_string($path)) {
            return '';
        }

        return trim($path, '/');
    }
}
