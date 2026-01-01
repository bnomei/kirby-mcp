# Kirby Panel extensions KB — plan

We are creating a **new** knowledge base collection under `/kb/panel` focused on Panel extension development
(PHP + Vue). These docs are **tool-first** for coding agents using Kirby MCP in this repo, with a
Kirby 5 → 6 migration focus.

## Goals

- Provide actionable playbooks for building Panel extensions (areas, fields, sections, dialogs, dropdowns, searches).
- Default to modern Panel tooling: `kirbyup` for bundling/HMR and `kirbyuse` for DX and typings.
- Capture the PHP ↔ Vue data flow (props, API payloads, routes, `load()` for sections).
- Keep docs tightly linked to MCP tools/resources for inspection and verification.
- Maintain a curated list of canonical external references.
- Tag Kirby 5 vs Kirby 6 differences inline, prioritizing migration guidance.

## Scope / non-goals

### In scope

- Panel plugin structure under `site/plugins/*`.
- Extension points: areas, views, fields, sections, dialogs, dropdowns, searches.
- Build workflow decisions (bundled vs no build process).
- Panel UI components and reuse guidance (Panel Lab + Panel repo).
- Compatibility notes (Kirby 5/6, Vue version expectations, migration notes).

### Out of scope

- Full Vue or Vite tutorials.
- Re-documenting the entire Panel UI kit (link out instead).
- General plugin marketplace, licensing, or commercial packaging guidance.
- Non-Panel extensions (hooks, tags) unless they directly affect Panel plugins.

## Primary sources (default references)

- Panel UI reference: https://getkirby.com/docs/reference/plugins/ui
- Panel Lab: https://lab.getkirby.com/public/lab
- Kirby Panel source (for real component usage): https://github.com/getkirby/kirby/tree/main/panel
- kirbyup (bundler + HMR): https://github.com/johannschopplich/kirbyup
- kirbyuse (Panel composables + typings): https://github.com/johannschopplich/kirbyuse
- Cookbook Panel recipes:
  - https://getkirby.com/docs/cookbook/panel/first-panel-area
  - https://getkirby.com/docs/cookbook/panel/advanced-panel-area
  - https://getkirby.com/docs/cookbook/panel/first-panel-field
  - https://getkirby.com/docs/cookbook/panel/first-panel-section
- Bundling decision: https://getkirby.com/docs/cookbook/plugins/to-bundle-or-not-to-bundle

## Folder organization (flattened)

All docs live directly under `kb/panel/` (no subfolders). Use clear, slugified filenames
with prefixes for grouping:

- `INDEX.md` — entry point with categories + links.
- `reference-areas.md`
- `reference-fields.md`
- `reference-sections.md`
- `reference-dialogs.md`
- `reference-dropdowns.md`
- `reference-searches.md`
- `tooling-kirbyup.md`
- `tooling-kirbyuse.md`
- `tooling-bundling.md`
- `patterns-view-props.md`
- `patterns-section-load.md`
- `patterns-dialog-flow.md`
- `patterns-component-reuse.md`
- `compat-k5-k6-migration.md` (cross-cutting differences and checks)

## Doc formats

### Recipe (task playbook)

```md
# <Recipe title>

## Goal

## Inputs to ask for

## MCP tools/resources to use

## Files to touch

## Implementation steps

## Examples

## Verification

## Links

## Version notes (K5/K6)
```

### Reference (extension point)

```md
# <Extension> (type: <areas|fields|sections|...>)

## What it is

## PHP registration

## Vue registration

## Data flow (props/events/load)

## Common UI components

## Gotchas

## MCP: Inspect/verify

## Links

## Version notes (K5/K6)
```

### Tooling (build + DX)

```md
# <Tooling topic>

## When to use

## Minimal setup

## Dev workflow

## Build output

## Compatibility notes

## MCP: Inspect/verify

## Links

## Version notes (K5/K6)
```

### Version tags

Use short tags inside sections where behavior diverges:

- `K5:` guidance for Kirby 5
- `K6:` guidance for Kirby 6
- `K5 → K6:` migration notes (what to change or verify)

## Baseline MCP workflow (Panel extensions)

1. Establish context: `kirby_init` or `kirby://info`.
2. Resolve paths: `kirby://roots` before referencing `site/plugins`.
3. Verify plugin registration: `kirby_plugins_index` (runtime truth).
4. Confirm blueprint usage: `kirby_blueprints_index` + `kirby_blueprint_read`.
5. Check config for dev mode: `kirby://config/panel.dev` (via `kirby_config`).
6. Prefer resource reads over raw CLI; use `kirby_run_cli_command` only when needed.

## Scenario coverage (Cookbook Panel)

The cookbook recipes should be represented as **scenarios** under `kb/scenarios/`
and cross-linked from the Panel KB (see scenarios plan). Use slugified names that
start with `panel-`:

- `panel-area-basic.md` — Basic Panel area (view route + props + component).
- `panel-area-advanced.md` — Advanced area (dialogs, dropdowns, searches, subviews, sorting).
- `panel-field-custom.md` — Custom Panel field (props, events, reuse UI components).
- `panel-section-custom.md` — Custom Panel section (props + `load()` flow).
- `panel-bundling-decisions.md` — When to bundle vs no build process (SFC → plain JS).

## Enhancements to existing KB

- `kirby://kb/scenarios/15-custom-blocks-nested-blocks`:
  - Add `kirbyup`/`kirbyuse` defaults and bundling decision link.
  - Link to new `kirby://kb/panel/...` references for UI component reuse.
- `kirby://kb/glossary/panel` (or new terms):
  - Add `panel-area`, `panel-field`, `panel-section`, `kirbyup`, `kirbyuse`.

## Rollout phases

1. Tooling + bundling docs (`kirbyup`, `kirbyuse`, bundle/no-bundle).
2. Cookbook-aligned recipes (areas/fields/sections).
3. Reference docs for extension points (dialogs, dropdowns, searches).
4. Patterns + UI component reuse (Panel Lab + Panel repo).

## Search integration

Ensure `kirby_search` includes `kb/panel/` alongside the other KB roots.
