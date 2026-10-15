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

- Teach Kirby 6 directly. Keep the verified baseline and general version caveats in the migration guides; repeat a version qualifier only when it affects a specific API or tooling instruction. Do not map CMS versions to MCP release lines in task guidance.
- Preserve the v1.15.1 maintenance improvements and published article paths. Keep section resources useful for existing configuration: built-in sections remain supported through automatic conversion. Prefer canonical fields for new work, without implying all older syntax is removed. Label actual Kirby 5 code sketches and link their migration instructions.
- Entry point: `scenarios/83-migrate-kirby-5-to-6.md`. A `pages` section becomes `pagelist`, but a `pages` picker becomes `pagepicker` (likewise files/filelist/filepicker). Built-in sections auto-convert; custom sections require a field-class port.
- Keep its coverage map synchronized with developer-facing release/changelog changes. Put current PHP contracts in `scenarios/84-kirby-6-core-contracts.md`, editor workflows in `panel/reference-editor.md`, and component/request contracts in `panel/reference-runtime.md`; link them from task-specific guides. Ordinary recipes lead with Kirby 6, followed by concise existing-project migration notes, not Vue 2 code sketches.
- Display fields never receive content update payloads. Schema `hasValue` can be true, false or null; null is unresolved, not writable. Generated update-schema links require `hasValue === true` and an actual guide; unknown/static fields omit links and aliases are not guessed. Direct reference resources remain readable. Blueprint load data is raw; model fields are normalized.
- For future passes, prioritize `docs:` commits in `getkirby/getkirby.com`, relevant documentation fixes with other prefixes, and stable release notes in `getkirby/kirby`. Verify claims against linked official pages and use runtime checks for storage/payload changes.
- `PLAN.md` files are historical authoring plans, not verified reference material. In particular, the old update-schema seed examples for entries/link are superseded by `entries.md` and `link.md`.

## Guardrails

- No secrets/tokens/private URLs.
- Keep Markdown plain (no binaries); avoid huge files.
- If you rename/move articles, update any tests/tools that reference paths or titles.
