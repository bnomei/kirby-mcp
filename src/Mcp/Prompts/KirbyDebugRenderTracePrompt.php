<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Prompts;

use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpPrompt;

final class KirbyDebugRenderTracePrompt
{
    /**
     * @return array<int, array{role: 'assistant'|'user', content: string}>
     */
    #[McpPrompt(
        name: 'kirby_debug_render_trace',
        description: 'Debug by reproducing via kirby_render_page and inspecting mcp_dump traces via kirby_dump_log_tail.',
        meta: [
            'requiresRuntimeInstall' => true,
            'primaryTools' => [
                'kirby_init',
                'kirby_runtime_status',
                'kirby_runtime_install',
                'kirby_render_page',
                'kirby_dump_log_tail',
                'kirby_roots',
                'kirby_templates_index',
                'kirby_snippets_index',
                'kirby_controllers_index',
                'kirby_models_index',
            ],
        ],
    )]
    public function debugRenderTrace(
        #[CompletionProvider(values: ['html', 'json', 'rss', 'xml', 'txt'])]
        string $contentType = 'html',
    ): array {
        $assistant = <<<'TEXT'
You are a senior Kirby debugging engineer.

Rules:
- Reproduce first (`kirby_render_page`), then instrument (`mcp_dump()`), then fix.
- Keep changes minimal; remove temporary dumps after verification.
- Never guess paths; use roots + index tools.
- Keep tool calls tight: render → inspect → instrument only if needed.
- If Kirby terminology is unclear, consult `kirby://glossary` and `kirby://glossary/{term}`.
TEXT;

        $user = sprintf(<<<'TEXT'
Debug a Kirby rendering issue (contentType: %s).

Glossary quick refs (optional):
- kirby://glossary/template
- kirby://glossary/snippet
- kirby://glossary/controller
- kirby://glossary/page-model
- kirby://glossary/content-representation
- kirby://glossary/route

Do:
1) Minimum repro inputs to ask for:
   - page id or uuid (preferred) or a URL path
   - expected vs actual output + any error text
   - whether it depends on session/login/cookies

2) Prep:
   - call `kirby_init`
   - ensure runtime: `kirby_runtime_status` → `kirby_runtime_install` if needed

3) Reproduce in runtime:
   - call `kirby_render_page(id=..., contentType=%s, noCache=true)`
   - capture errors + `traceId`

4) Locate code:
   - use `kirby://roots` + `kirby_templates_index`/`kirby_snippets_index`/`kirby_controllers_index`/`kirby_models_index`

5) If the error is unclear, instrument briefly:
   - add a few targeted `mcp_dump()` calls near the suspected code path
   - re-render and inspect with `kirby_dump_log_tail(traceId=...)`

6) Fix + verify:
   - implement the smallest fix
   - re-run `kirby_render_page(noCache=true)`
   - remove temporary `mcp_dump()` and summarize root cause + verification.
TEXT, $contentType, $contentType);

        return [
            ['role' => 'assistant', 'content' => $assistant],
            ['role' => 'user', 'content' => $user],
        ];
    }
}
