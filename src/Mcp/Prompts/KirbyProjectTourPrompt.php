<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Prompts;

use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpPrompt;

final class KirbyProjectTourPrompt
{
    /**
     * @return array<int, array{role: 'assistant'|'user', content: string}>
     */
    #[McpPrompt(
        name: 'kirby_project_tour',
        description: 'Map the project (roots + inventory) and suggest next steps.',
        meta: [
            'primaryTools' => [
                'kirby_init',
                'kirby_roots',
                'kirby_templates_index',
                'kirby_snippets_index',
                'kirby_controllers_index',
                'kirby_models_index',
                'kirby_blueprints_index',
                'kirby_plugins_index',
                'kirby_runtime_status',
                'kirby_runtime_install',
            ],
        ],
    )]
    public function projectTour(
        #[CompletionProvider(values: ['quick', 'standard', 'deep'])]
        string $depth = 'standard',
    ): array {
        $assistant = <<<'TEXT'
You are a senior Kirby CMS engineer.

Rules:
- Use Kirby MCP tools/resources as the source of truth.
- Never assume paths; read `kirby://roots` before referencing folders.
- Prefer parallel tool calls where possible.
- If Kirby terminology is unclear, consult `kirby://glossary` and `kirby://glossary/{term}`.
- Ask only the minimum clarifying questions; then proceed.
TEXT;

        $user = sprintf(<<<'TEXT'
Give me a Kirby project tour (depth: %s).

Glossary quick refs (optional):
- kirby://glossary/roots
- kirby://glossary/template
- kirby://glossary/snippet
- kirby://glossary/controller
- kirby://glossary/page-model
- kirby://glossary/blueprint
- kirby://glossary/content

Do:
1) Context: call `kirby_init` (or read `kirby://info` + `kirby://composer`) and extract versions + “how to run” scripts.
2) Roots: read `kirby://roots` and summarize where templates/snippets/controllers/models/blueprints/content/config live.
3) Inventory (prefer parallel): `kirby_templates_index`, `kirby_snippets_index`, `kirby_controllers_index`, `kirby_models_index`, `kirby_blueprints_index`, `kirby_plugins_index`.
4) Runtime (only if needed): `kirby_runtime_status` → `kirby_runtime_install` → re-run relevant index tools.
5) Output:
   - “Where to edit what” cheat sheet (template vs controller vs snippet vs blueprint vs content vs config).
   - Notable customizations (page models, plugins, blueprint structure, unusual roots).
   - If depth ≠ `quick`: highlight key config (`kirby://config/debug`, `kirby://config/cache`, `kirby://config/routes`, `kirby://config/languages`) when available.
   - 3 next-step recommendations (DX/perf/security).
TEXT, $depth);

        return [
            ['role' => 'assistant', 'content' => $assistant],
            ['role' => 'user', 'content' => $user],
        ];
    }
}
