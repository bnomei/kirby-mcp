<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Prompts;

use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpPrompt;

final class KirbyUpgradeKirbyPrompt
{
    /**
     * @return array<int, array{role: 'assistant'|'user', content: string}>
     */
    #[McpPrompt(
        name: 'kirby_upgrade_kirby',
        description: 'Upgrade Kirby safely (official docs + composer + verification).',
        meta: [
            'primaryTools' => [
                'kirby_init',
                'kirby_info',
                'kirby_composer_audit',
                'kirby_plugins_index',
                'kirby_online',
                'kirby_cli_version',
                'kirby_runtime_status',
                'kirby_runtime_install',
                'kirby_render_page',
            ],
        ],
    )]
    public function upgradeKirby(
        #[CompletionProvider(values: ['latest-stable', 'latest', '5.x'])]
        string $target = 'latest-stable',
    ): array {
        $assistant = <<<'TEXT'
You are a senior Kirby upgrade engineer.

Rules:
- Treat the official upgrade docs as the source of truth; don’t guess breaking changes.
- Keep changes incremental; verify often (tests/analysis + render smoke checks).
- Watch plugin compatibility and config deprecations closely.
- Ask for confirmation before dependency upgrades that change the lockfile.
- If Kirby terminology is unclear, consult `kirby://glossary` and `kirby://glossary/{term}`.
TEXT;

        $user = sprintf(<<<'TEXT'
Upgrade this Kirby project (target: %s).

Glossary quick refs (optional):
- kirby://glossary/plugin
- kirby://glossary/option
- kirby://glossary/panel
- kirby://glossary/page-model
- kirby://glossary/permissions

Do:
1) Baseline:
   - call `kirby_init` (captures versions + composer audit)
   - extract the project’s “how to run” commands from `kirby://composer`

2) Compatibility surface:
   - inventory plugins: `kirby_plugins_index`
   - note current `getkirby/cms` constraint + lockfile version (composer audit)

3) Docs-first checklist:
   - use `kirby_online` to find upgrade guides and breaking changes for the target
   - produce a short checklist of required code/config changes for THIS project

4) Upgrade implementation:
   - update composer constraints and run the appropriate composer update steps
   - adjust code/config per the checklist; keep diffs minimal and well-scoped

5) Verify:
   - run the project’s tests/analysis/formatting commands discovered in step (1)
   - call `kirby_cli_version` to confirm the new version
   - ensure runtime commands are in sync: `kirby_runtime_status` → `kirby_runtime_install` if needed
   - render key pages with `kirby_render_page(noCache=true)`

6) Summarize:
   - what changed, remaining risks/unknowns, and a short manual QA checklist (Panel login, forms, permissions).
TEXT, $target);

        return [
            ['role' => 'assistant', 'content' => $assistant],
            ['role' => 'user', 'content' => $user],
        ];
    }
}
