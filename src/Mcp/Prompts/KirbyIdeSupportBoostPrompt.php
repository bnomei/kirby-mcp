<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Prompts;

use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpPrompt;

final class KirbyIdeSupportBoostPrompt
{
    /**
     * @return array<int, array{role: 'assistant'|'user', content: string}>
     */
    #[McpPrompt(
        name: 'kirby_ide_support_boost',
        description: 'Improve IDE support via status + minimal type-hint fixes + optional helper generation.',
        meta: [
            'primaryTools' => [
                'kirby_init',
                'kirby_ide_helpers_status',
                'kirby_generate_ide_helpers',
                'kirby_templates_index',
                'kirby_snippets_index',
                'kirby_controllers_index',
                'kirby_models_index',
            ],
        ],
    )]
    public function ideSupportBoost(
        #[CompletionProvider(values: ['skip', 'dry-run', 'write'])]
        string $helpers = 'dry-run',
    ): array {
        $assistant = <<<'TEXT'
You are a Kirby DX engineer focused on IDE reliability (types, PHPDoc hints, regeneratable helpers).

Rules:
- Make “types-only” edits unless explicitly asked to change behavior.
- Run `kirby_ide_helpers_status` before generating helpers.
- Generate helpers with `dryRun=true` first; only write after confirmation.
- Keep diffs minimal; avoid refactors.
- If Kirby terminology is unclear, consult `kirby://glossary` and `kirby://glossary/{term}`.
TEXT;

        $user = sprintf(<<<'TEXT'
Improve IDE support for this Kirby project (helpers: %s).

Glossary quick refs (optional):
- kirby://glossary/template
- kirby://glossary/snippet
- kirby://glossary/controller
- kirby://glossary/page-model
- kirby://glossary/blueprint

Do:
1) Initialize: call `kirby_init`.
2) Assess: call `kirby_ide_helpers_status` (use details only if needed).
3) Fix (minimal, types-only):
   - add missing template/snippet `@var` hints where it helps
   - type-hint controller closures
   - ensure page models extend the correct Kirby classes
4) Helpers:
   - if helpers = `skip`: stop after step (3)
   - if helpers = `dry-run`: call `kirby_generate_ide_helpers(dryRun=true)` and summarize planned changes
   - if helpers = `write`: do dry-run first, ask for confirmation, then call `kirby_generate_ide_helpers(dryRun=false)`
5) Re-check: re-run `kirby_ide_helpers_status` and summarize improvements.
TEXT, $helpers);

        return [
            ['role' => 'assistant', 'content' => $assistant],
            ['role' => 'user', 'content' => $user],
        ];
    }
}
