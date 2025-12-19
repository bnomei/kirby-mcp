<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Prompts;

use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpPrompt;

final class KirbyPerformanceAuditPrompt
{
    /**
     * @return array<int, array{role: 'assistant'|'user', content: string}>
     */
    #[McpPrompt(
        name: 'kirby_performance_audit',
        description: 'Guide an agent through a Kirby performance audit (cache + query pitfalls). Use before making performance-related changes.',
        meta: [
            'primaryTools' => [
                'kirby_init',
                'kirby_info',
                'kirby_composer_audit',
                'kirby_roots',
                'kirby_templates_index',
                'kirby_snippets_index',
                'kirby_controllers_index',
                'kirby_models_index',
                'kirby_plugins_index',
                'kirby_runtime_status',
                'kirby_runtime_install',
                'kirby_render_page',
            ],
        ],
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
        string $focus = 'general',
        #[CompletionProvider(values: ['quick', 'standard', 'deep'])]
        string $depth = 'standard',
    ): array {
        $assistant = <<<'TEXT'
You are a senior Kirby CMS performance engineer.

Rules:
- Prefer runtime truth via Kirby MCP tools/resources; don’t guess.
- Make minimal, low-risk changes first and explain impact.
- Verify with the project’s own test/analysis commands when available.
- Avoid over-searching; stop once findings are actionable.
- If Kirby terminology is unclear, consult `kirby://glossary` and `kirby://glossary/{term}`.
TEXT;

        $user = sprintf(<<<'TEXT'
Run a Kirby performance audit (focus: %s, depth: %s).

Glossary quick refs (optional):
- kirby://glossary/cache
- kirby://glossary/collection
- kirby://glossary/pagination
- kirby://glossary/query-language
- kirby://glossary/plugin
- kirby://glossary/route

Do:
1) Context:
   - call `kirby_init` (or read `kirby://composer` + `kirby://roots`)
   - identify how to run tests/analysis/formatting from the composer audit
   - if you need runtime rendering/content tools, ensure runtime: `kirby_runtime_status` → `kirby_runtime_install`

2) Find high-impact pitfalls (prioritize):
   - full-site traversal (`site()->index()` / `$site->index()`) and unbounded queries
   - N+1 patterns (queries inside loops): repeated `children()`, `files()`, `images()`, `find()`, `search()` etc.
   - heavy work in templates/controllers that should be narrowed or memoized
   - unbounded filesystem scans on requests

3) Caching review:
   - inspect page cache settings and any `isCacheable()` overrides
   - if you render, compare `kirby_render_page` with/without `noCache`

4) Propose fixes (small first):
   - prefer narrowing queries (targeted roots, limits, early filters) over “cache everything”
   - where caching is appropriate, use explicit cache keys (`$kirby->cache(...)`) and clear invalidation rules

5) Verify + report:
   - run project checks (tests/analysis)
   - summarize: top issues, proposed/implemented fixes, expected impact, and remaining risks
   - if depth = `quick`, keep to the top 5 findings; if `deep`, include a prioritized checklist.
TEXT, $focus, $depth);

        return [
            ['role' => 'assistant', 'content' => $assistant],
            ['role' => 'user', 'content' => $user],
        ];
    }
}
