# Kirby KB glossary — plan

We are creating a **new** set of knowledgebase documents as Markdown files under `/kb/kirby/glossary`.

These docs are a **prompt-first glossary**: each file explains a single Kirby term (often something users write verbatim in prompts like `$page`, `$file`, “blueprint”, “snippet”, “Panel”, “KQL”) and shows how an agent can **verify/inspect it in the current project** using the Kirby MCP tools/resources in this repo.

This is complementary to:
- `knowledge/kirby/` (deeper, topic-oriented notes)
- `kb/kirby/scenarios/` (task playbooks; out of scope here)

## Goals

- Maximize **KB search recall** for common Kirby terms used in prompts (including code-ish tokens).
- Provide fast “what is this?” mapping: **term → Kirby concept → where to inspect → what to change**.
- Be **tool-first**: link each term to the **relevant MCP tools/resources** that can confirm reality in a specific project.

## Scope / non-goals

### In scope (code-first)
- Core runtime concepts: `kirby()`, `site()`, `page()`, `$kirby`, `$site`, `$page`, `$file`
- Objects & collections: `Kirby\Cms\Page`, `Pages`, `File`, `Files`, `Field`, `User`, …
- Templates/snippets/controllers/models and their naming/resolution rules
- Content model terms used in coding: content files, fields, `uid`, `id`, `uuid`, slugs, representations
- Panel/blueprints: blueprint ids, `extends`, fields, sections, blocks, layouts, query language
- Plugins & extension points: `Kirby::plugin()`, extensions, hooks, routes, KirbyTags/KirbyText
- CLI / DX concepts that show up in prompts: Kirby CLI commands, “roots”, IDE helper generation

### Out of scope (avoid)
- Rewriting full Kirby documentation or listing every method/option
- Generic PHP/HTML/CSS concepts not specific to Kirby
- Rare or purely marketing terms unless they commonly appear in user prompts

## File naming & organization

- One term per file: `kb/kirby/glossary/<slug>.md`
- Slug rules:
  - lowercase, dash-separated, no symbols
  - `$page` → `page`, `kirby()` → `kirby`, `KirbyText` → `kirbytext`
- Prefer a **canonical** file per concept; include synonyms/variants as **aliases** in the same file.
- Use cross-links to related terms via relative Markdown links.

Optional (later): add `kb/kirby/glossary/INDEX.md` as an alphabetical list of entries.

## Glossary entry format (what every file should contain)

Each glossary doc should be short, operational, and tool-first:
- **Meaning** (Kirby context, 3–8 sentences)
- **In prompts** (what users usually mean when they say it)
- **Variants / aliases** (plural forms, class names, helper functions, Panel vs frontend usage)
- **Example** (small, correct snippets; avoid huge code blocks)
- **MCP: Inspect/verify** (which internal tools/resources to call, in what order)
- **Related terms** (local cross-links)
- **Links** (official Kirby docs only; keep at the bottom)

### Glossary entry template

```md
# <Term> (aliases: <alias1>, <alias2>, ...)

## Meaning

## In prompts (what it usually implies)

## Variants / aliases

## Example

## MCP: Inspect/verify

## Related terms

## Links
```

## Baseline agent workflow (Kirby MCP)

1. Establish context first:
   - `kirby_init` (or read `kirby://info` + `kirby://roots`)
2. Never assume paths:
   - Always start with `kirby_roots` / `kirby://roots` before referencing `site/`, `content/`, etc.
3. Prefer “inventory” tools to discover what exists:
   - `kirby_templates_index`, `kirby_snippets_index`, `kirby_controllers_index`, `kirby_models_index`
   - `kirby_blueprints_index`, `kirby_blueprints_loaded`
   - `kirby_plugins_index`
4. Prefer resource reads when possible (structured + stable):
   - `kirby://blueprint/{encodedId}` (or `kirby_blueprint_read`)
   - `kirby://config/{option}`
   - `kirby://page/content/{encodedIdOrUuid}` (or `kirby_read_page_content`)
5. Validate by rendering and inspecting runtime output:
   - `kirby_render_page` (HTML/JSON render with error capture)
   - If you need to see intermediate values *during* rendering, add temporary `mcp_dump()` calls and inspect via `kirby_dump_log_tail(traceId=...)`
   - Use `kirby_eval` for small, read-only “what is this value?” checks (not for render-time tracing)
6. Only drop down to raw CLI when needed:
   - `kirby://commands` + `kirby://cli/command/{command}`
   - `kirby_run_cli_command` (guarded allowlist; set `allowWrite=true` only when required)

## Term discovery workflow (relentless, but curated)

We want broad discovery, then strong pruning to “high-signal for prompts”.

1. Crawl the official Kirby glossary (seed terms):
   - Start at https://getkirby.com/docs/glossary
   - Use Tavily crawl/map to collect all linked glossary pages and their titles/term names
2. Expand via the official reference (code-first terms):
   - https://getkirby.com/docs/reference
   - Focus on:
     - objects (`Kirby\Cms\Page`, `File`, `Field`, `App`, …)
     - collections (`Pages`, `Files`, `Users`, …)
     - helper functions (`page()`, `site()`, `kirby()`, `snippet()`, `option()`, …)
     - blueprint & Panel building blocks (fields/sections/blocks/layouts/query language)
3. Add “MCP-first” terms that map directly to our tools/resources:
   - roots, blueprint id/encoding, content representations, render, IDE helpers, CLI commands
4. De-duplicate and decide canonical entries:
   - Merge synonyms into one file; list aliases near the top for search recall
   - Skip items that are obvious from local code inspection and don’t benefit from prose
5. Write entries with search in mind:
   - Include the raw tokens users type (e.g. `$page`, `Kirby\Cms\Page`, `page()`), but also the plain word (“page”)
   - Include common phrasing (“page object”, “current page”, “template variable”, “Panel blueprint”)

## Internal MCP tools/resources to link from entries (reference inventory)

### Session / context
- Tools: `kirby_init`, `kirby_info`
- Resources: `kirby://info`, `kirby://composer`, `kirby://roots`, `kirby://tools`

### Project discovery (what exists in *this* project)
- Tools: `kirby_roots`
- Tools: `kirby_templates_index`, `kirby_snippets_index`, `kirby_controllers_index`, `kirby_models_index`
- Tools: `kirby_blueprints_index`, `kirby_blueprints_loaded`, `kirby_blueprint_read`
- Resource template: `kirby://blueprint/{encodedId}`
- Tools: `kirby_plugins_index`

### Runtime inspection / verification
- Tools: `kirby_render_page`, `kirby_dump_log_tail`, `kirby_routes_index`, `kirby_eval`
- Helper: `mcp_dump()` (log values from routes/controllers/templates; read via `kirby_dump_log_tail`)
- Tools: `kirby_read_page_content`, `kirby_update_page_content` (only when explicitly requested)
- Resource template: `kirby://page/content/{encodedIdOrUuid}`

### CLI discovery
- Resource: `kirby://commands`
- Resource template: `kirby://cli/command/{command}`
- Tool: `kirby_run_cli_command`

### Official docs lookup (avoid manual browsing)
- Tool: `kirby_online` (official Kirby site index + fetch `.md`)

### Local KB lookup (implementation detail; keep in mind)
- Tool: `kirby_search` searches the bundled local KB under `kb/`
- Plan: once the glossary is populated, extend/duplicate KB search to include `kb/kirby/glossary/`

## Rollout phases (recommended order)

1. Core runtime objects & helpers (highest prompt frequency): `$page`, `$site`, `$kirby`, `page()`, `site()`, `kirby()`, `snippet()`
2. Files/content model: `$file`, `$files`, fields, content files, `uid`/`id`/`uuid`
3. Templates/snippets/controllers/models: resolution rules and where code lives (root-aware)
4. Panel/blueprints: blueprint ids, `extends`, fields/sections, blocks/layouts, query language
5. Plugins/extensions/routing/representations: hooks, KirbyTags/KirbyText, routes, JSON reps, KQL
6. CLI/DX terms: Kirby CLI commands, IDE helper generation, cache/media, diagnostics

## Seed entry list (starting point, will be refined by crawling)

Start with these terms because they show up constantly in prompts and code:
- `page`
- `site`
- `kirby`
- `file`
- `pages`
- `files`
- `field`
- `template`
- `snippet`
- `controller`
- `page-model`
- `blueprint`
- `roots`
- `panel`
- `extends`
- `query-language`
- `block`
- `layout`
- `kirbytext`
- `kirbytag`
- `plugin`
- `hook`
- `route`
- `content-representation`
- `kql`
- `uuid`
