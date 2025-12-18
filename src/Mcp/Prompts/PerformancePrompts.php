<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Prompts;

use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpPrompt;

final class PerformancePrompts
{
    /**
     * A Kirby-focused performance audit prompt (caching + query pitfalls).
     *
     * This is meant to be used as a “workflow template”: it instructs the agent
     * to first gather project context via MCP tools/resources, then scan code,
     * then propose and verify changes with the project’s own test/toolchain.
     *
     * @return array<int, array{role: 'assistant'|'user', content: string}>
     */
    #[McpPrompt(
        name: 'kirby_performance_audit',
        description: 'Guide an agent through a Kirby performance audit (cache + query pitfalls). Use before making performance-related changes.',
    )]
    public function performanceAudit(
        #[CompletionProvider(values: [
            'general',
            'caching',
            'collections',
            'queries',
            'templates',
            'controllers',
            'page-models',
            'routes',
            'blueprints',
            'plugins',
            'panel',
            'media',
            'deployment',
        ])]
        string $focus = 'general'
    ): array {
        $assistant = <<<'TEXT'
You are a senior Kirby CMS performance engineer.

Operating principles:
- Prefer “runtime truth” via Kirby CLI-backed MCP tools/resources.
- Make changes minimal and explain their impact and risk.
- Never edit content files directly unless explicitly requested.
- Use the project’s own scripts for tests/analysis/formatting.
TEXT;

        $user = sprintf(<<<'TEXT'
Run a Kirby performance audit (focus: %s).

Workflow:
1) Initialize + gather context:
   - Call `kirby_init` (or read `kirby://project/composer` + `kirby://project/roots`).
   - Identify the available quality tools/commands (Pest/PHPUnit, PHPStan/Larastan, Pint, etc.).
   - If runtime commands are needed, call `kirby_runtime_install`.

2) Find common Kirby performance pitfalls (prioritize):
   - Any use of `site()->index()` / `$site->index()` (full-site traversal); see if it’s avoidable or can be constrained.
   - Broad queries inside loops (N+1 patterns): repeated `children()`, `files()`, `images()`, `find()`, `search()` without caching.
   - Heavy template/controller work that should be memoized or moved to cached collections.
   - Unbounded filesystem scans (e.g. global file searches) during requests.

3) Check caching behavior:
   - Look for page cache settings and any `isCacheable()` overrides.
   - If the project uses page rendering in the audit, compare `kirby_render_page` with and without `noCache`.

4) Propose fixes:
   - Prefer narrowing queries (targeted roots, limiting depth, early filters) over caching-everything.
   - Where caching is appropriate, prefer Kirby cache instances (`$kirby->cache(...)`) and make cache keys explicit.

5) Verify:
   - Run the project’s own test + analysis commands discovered in step (1).
   - Summarize changes and remaining risks/unknowns.
TEXT
            , $focus);

        return [
            ['role' => 'assistant', 'content' => $assistant],
            ['role' => 'user', 'content' => $user],
        ];
    }
}
