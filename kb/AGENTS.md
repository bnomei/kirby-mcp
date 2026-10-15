# Knowledge Base Guidelines

## Mission

Maintain the bundled Markdown knowledge base shipped with Kirby MCP (used by tools like `kirby_search`).

## System

- Shipped KB content is under `kb/`:
  - `kb/glossary/*.md` – short definitions (file name is the term slug).
  - `kb/scenarios/*.md` – longer how-to guides (typically `NN-title.md`; Panel extension scenarios may use `panel-` prefixes).
  - `kb/update-schema/` – content field update schema guidance (storage + payloads) plus blueprint update guides (`blueprint-*.md`).
  - `kb/panel/` – Panel extension development KB (recipes, reference, tooling).
- `kb/` is the curated output for the shipped knowledge base.
- KB resources: use `kirby://kb` to list documents and `kirby://kb/{path}` (no `.md`) to read a file.

## Workflows

- Add a glossary term: `kb/glossary/<term>.md` with a clear H1 + concise body.
- Add a scenario: `kb/scenarios/NN-title.md` (avoid renames once published).
- Add a content field guide: `kb/update-schema/<field>.md` with storage and update details.
- Keep tool references in KB aligned with new MCP capabilities (e.g. query-language helpers).
- Format Markdown: `npm run format` (Prettier).

## Maintenance baseline

- The October 15, 2026 maintenance pass reviewed official documentation changes since January 10, 2026 and stable Kirby 5 releases through 5.6.1 (January baseline: 5.2.2), with a feature-coverage look-back at 5.1/5.2.
- Keep one canonical KB and update existing articles in place. Existing K6 notes may remain; this is not a separate migration-guide collection.
- For future passes, prioritize `docs:` commits in `getkirby/getkirby.com`, relevant documentation fixes with other prefixes, and stable release notes in `getkirby/kirby`. Verify claims against linked official pages and use runtime checks for storage/payload changes.
- `PLAN.md` files are historical authoring plans, not verified reference material. In particular, the old update-schema seed examples for entries/link are superseded by `entries.md` and `link.md`.

## Guardrails

- No secrets/tokens/private URLs.
- Keep Markdown plain (no binaries); avoid huge files.
- If you rename/move articles, update any tests/tools that reference paths or titles.
