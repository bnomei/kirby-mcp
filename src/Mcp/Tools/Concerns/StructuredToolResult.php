<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools\Concerns;

use Mcp\Schema\Content\ResourceLink;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\RequestContext;

trait StructuredToolResult
{
    /**
     * @param array<mixed> $payload
     * @return array<mixed>|CallToolResult
     */
    protected function maybeStructuredResult(?RequestContext $context, array $payload): array|CallToolResult
    {
        if ($context === null) {
            return $payload;
        }

        try {
            $json = json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE
            );
        } catch (\Throwable) {
            $json = '{}';
        }

        $content = [new TextContent($json)];
        if ($context->getProtocolVersion()->isModern()) {
            foreach ($this->resourceLinkCandidates($payload) as $uri => $title) {
                $content[] = new ResourceLink(uri: $uri, name: $uri, title: $title);
            }
        }

        return new CallToolResult(
            content: $content,
            structuredContent: $payload,
        );
    }

    /**
     * Extract only URI-bearing fields owned by tool contracts. Do not inspect
     * arbitrary result or user content for URI-looking strings.
     *
     * @param array<mixed> $payload
     * @return array<string, string|null>
     */
    private function resourceLinkCandidates(array $payload): array
    {
        $candidates = [];

        foreach (['schemaRefs', 'BEFORE_UPDATE_READ', 'schemaCheckReminder'] as $field) {
            $values = $payload[$field] ?? [];
            if (!is_array($values)) {
                continue;
            }

            foreach ($values as $uri) {
                $this->addResourceLinkCandidate($candidates, $uri);
            }
        }

        $suggestions = $payload['suggestions'] ?? [];
        if (is_array($suggestions)) {
            foreach ($suggestions as $suggestion) {
                if (!is_array($suggestion)) {
                    continue;
                }

                $this->addResourceLinkCandidate(
                    $candidates,
                    $suggestion['name'] ?? null,
                    is_string($suggestion['title'] ?? null) ? $suggestion['title'] : null,
                );
            }
        }

        return $candidates;
    }

    /** @param array<string, string|null> $candidates */
    private function addResourceLinkCandidate(array &$candidates, mixed $uri, ?string $title = null): void
    {
        if (!is_string($uri)
            || !str_starts_with($uri, 'kirby://')
            || str_contains($uri, '{')
            || str_contains($uri, '}')
            || array_key_exists($uri, $candidates)
        ) {
            return;
        }

        $candidates[$uri] = $title;
    }
}
