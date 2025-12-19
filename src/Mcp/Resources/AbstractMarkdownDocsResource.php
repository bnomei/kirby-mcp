<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Bnomei\KirbyMcp\Support\StaticCache;
use Mcp\Exception\ResourceReadException;

abstract class AbstractMarkdownDocsResource
{
    protected const USER_AGENT = 'kirby-mcp (MCP server)';

    public function __construct(
        protected readonly ProjectContext $context = new ProjectContext(),
    ) {
    }

    protected function fetchCachedMarkdown(string $cacheKey, string $markdownUrl, ?int $ttlSeconds = null): string
    {
        $ttlSeconds ??= $this->docsTtlSeconds();

        if ($ttlSeconds > 0) {
            $cached = StaticCache::get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        try {
            $markdown = $this->httpGet($markdownUrl, 'text/plain');
        } catch (\Throwable $exception) {
            throw new ResourceReadException('Failed to fetch ' . $markdownUrl . ': ' . $exception->getMessage(), 0, $exception);
        }

        if ($ttlSeconds > 0 && $markdown !== '') {
            StaticCache::set($cacheKey, $markdown, $ttlSeconds);
        }

        return $markdown;
    }

    protected function docsTtlSeconds(): int
    {
        try {
            return KirbyMcpConfig::load($this->context->projectRoot())->docsTtlSeconds();
        } catch (\Throwable) {
            return KirbyMcpConfig::DEFAULT_DOCS_TTL_SECONDS;
        }
    }

    protected function httpGet(string $url, string $accept = 'application/json'): string
    {
        $userAgent = self::USER_AGENT;

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
}
