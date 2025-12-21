<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools\Concerns;

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

        return new CallToolResult(
            content: [new TextContent($json)],
            structuredContent: $payload,
        );
    }
}
