<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class McpToolIndex
{
    /**
     * @param array<string, int> $keywords
     */
    public function __construct(
        public string $whenToUse,
        public array $keywords = [],
    ) {
    }
}
