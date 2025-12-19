<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Prompts;

use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpPrompt;

final class KirbyScaffoldPageTypePrompt
{
    /**
     * @return array<int, array{role: 'assistant'|'user', content: string}>
     */
    #[McpPrompt(
        name: 'kirby_scaffold_page_type',
        description: 'Scaffold a new Kirby page type (blueprint + template, optional controller/page model) using project roots and conventions.',
        meta: [
            'primaryTools' => [
                'kirby_init',
                'kirby_roots',
                'kirby_templates_index',
                'kirby_controllers_index',
                'kirby_models_index',
                'kirby_blueprints_index',
                'kirby_blueprint_read',
                'kirby_runtime_status',
                'kirby_runtime_install',
                'kirby_render_page',
                'kirby_ide_helpers_status',
            ],
            'panelReferenceResources' => [
                'kirby://fields',
                'kirby://sections',
            ],
        ],
    )]
    public function scaffoldPageType(
        #[CompletionProvider(values: [
            'template+blueprint',
            'template+blueprint+controller',
            'template+blueprint+controller+model',
        ])]
        string $bundle = 'template+blueprint',
    ): array {
        $assistant = <<<'TEXT'
You are a senior Kirby CMS engineer.

Rules:
- Match existing project conventions; keep the initial scaffold minimal.
- Never assume paths; always resolve via `kirby://roots`.
- Verify by rendering (`kirby_render_page`) when possible.
- Prefer parallel inventory calls to reduce latency.
- If Kirby terminology is unclear, consult `kirby://glossary` and `kirby://glossary/{term}`.
TEXT;

        $user = sprintf(<<<'TEXT'
Scaffold a new Kirby page type (bundle: %s).

Glossary quick refs (optional):
- kirby://glossary/blueprint
- kirby://glossary/template
- kirby://glossary/controller
- kirby://glossary/page-model
- kirby://glossary/field
- kirby://glossary/section
- kirby://glossary/extends

Do:
1) Ask for the minimum inputs:
   - page type/template name (e.g. `event`, `project`, `article.video`)
   - required fields + Panel UX expectations
   - whether to copy/extend an existing type
   - where pages of this type should live

2) Discover project structure:
   - call `kirby_init`, then read `kirby://roots`
   - inventory: `kirby_templates_index`, `kirby_controllers_index`, `kirby_models_index`, `kirby_blueprints_index` (avoid collisions)
   - optionally read an existing blueprint via `kirby_blueprint_read` for patterns

3) Create the scaffold per the bundle:
   - blueprint: use `kirby://fields` + `kirby://sections` as reference
   - template: keep logic slim; prefer snippets for reuse
   - controller/model only if needed

4) Verify:
   - ensure a page exists that uses the template (Panel/content folder)
   - ensure runtime is available: `kirby_runtime_status` → `kirby_runtime_install` if needed
   - render with `kirby_render_page(noCache=true)` and fix errors

5) Optional DX:
   - run `kirby_ide_helpers_status` and apply minimal type-hint improvements.
TEXT, $bundle);

        return [
            ['role' => 'assistant', 'content' => $assistant],
            ['role' => 'user', 'content' => $user],
        ];
    }
}
