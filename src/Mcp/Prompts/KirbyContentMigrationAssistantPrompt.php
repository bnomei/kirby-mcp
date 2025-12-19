<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Prompts;

use Mcp\Capability\Attribute\CompletionProvider;
use Mcp\Capability\Attribute\McpPrompt;

final class KirbyContentMigrationAssistantPrompt
{
    /**
     * @return array<int, array{role: 'assistant'|'user', content: string}>
     */
    #[McpPrompt(
        name: 'kirby_content_migration_assistant',
        description: 'Plan/apply a safe content migration using kirby_read_page_content + kirby_update_page_content (explicit confirmation required).',
        meta: [
            'requiresRuntimeInstall' => true,
            'primaryTools' => [
                'kirby_init',
                'kirby_runtime_status',
                'kirby_runtime_install',
                'kirby_read_page_content',
                'kirby_update_page_content',
                'kirby_render_page',
                'kirby_roots',
            ],
        ],
    )]
    public function contentMigrationAssistant(
        #[CompletionProvider(values: ['plan', 'dry-run', 'apply'])]
        string $mode = 'plan',
    ): array {
        $assistant = <<<'TEXT'
You are a cautious Kirby content-migration engineer.

Rules:
- Never change content without explicit confirmation (even if mode=apply).
- Prefer runtime tools (`kirby_read_page_content` / `kirby_update_page_content`) over editing content files.
- Start with a small sample and stop on first unexpected result.
- Apply in small batches; stop on first error.
- If Kirby terminology is unclear, consult `kirby://glossary` and `kirby://glossary/{term}`.
TEXT;

        $user = sprintf(<<<'TEXT'
Run a Kirby content migration assistant (mode: %s).

Glossary quick refs (optional):
- kirby://glossary/content
- kirby://glossary/page
- kirby://glossary/field
- kirby://glossary/languages
- kirby://glossary/uuid

Do:
1) Ask for:
   - exact transformations (rename/move/merge/split/delete)
   - scope (which pages/sections/templates), include drafts?, languages?
   - whether any fields are derived/virtual and must NOT be written

2) Prep:
   - call `kirby_init`
   - ensure runtime: `kirby_runtime_status` → `kirby_runtime_install` if needed

3) Target selection:
   - prefer an explicit list of page ids/uuids from the user
   - if not available, derive from the content folder using `kirby://roots` (don’t guess)

4) Dry-run (always):
   - read a small sample with `kirby_read_page_content`
   - show a per-page diff summary (fields added/removed/changed) + edge cases

5) Apply (ONLY after confirmation):
   - write via `kirby_update_page_content` in small batches; stop on first error

6) Verify:
   - render representative pages with `kirby_render_page(noCache=true)`
   - summarize what changed, what was skipped, and follow-ups.
TEXT, $mode);

        return [
            ['role' => 'assistant', 'content' => $assistant],
            ['role' => 'user', 'content' => $user],
        ];
    }
}
