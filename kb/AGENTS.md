# Knowledge Base Guidelines

## Mission
Maintain the bundled Markdown knowledge base shipped with Kirby MCP (used by tools like `kirby_search`).

## System
- Shipped KB content is under `kb/kirby/`:
  - `glossary/*.md` – short definitions (file name is the term slug).
  - `scenarios/*.md` – longer how-to guides (`NN-title.md` keeps a stable order).
- `knowledge/` holds source material; `kb/` is the curated output.

## Workflows
- Add a glossary term: `kb/kirby/glossary/<term>.md` with a clear H1 + concise body.
- Add a scenario: `kb/kirby/scenarios/NN-title.md` (avoid renames once published).
- Format Markdown: `npm run format` (Prettier).

## Guardrails
- No secrets/tokens/private URLs.
- Keep Markdown plain (no binaries); avoid huge files.
- If you rename/move articles, update any tests/tools that reference paths or titles.
