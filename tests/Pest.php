<?php

declare(strict_types=1);

function cmsPath(): string
{
    return __DIR__ . '/cms';
}

beforeEach(function (): void {
    \Bnomei\KirbyMcp\Support\StaticCache::clear();
    \Bnomei\KirbyMcp\Mcp\ToolIndex::clearCache();
    \Bnomei\KirbyMcp\Mcp\PromptIndex::clearCache();
    \Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext::clearRootsCache();
    \Bnomei\KirbyMcp\Project\ComposerInspector::clearCache();
    \Bnomei\KirbyMcp\Project\KirbyMcpConfig::clearCache();
});
