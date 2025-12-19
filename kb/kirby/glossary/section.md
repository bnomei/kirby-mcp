# Section (aliases: “Panel section”, “blueprint section”)

## Meaning

Sections are blueprint building blocks used to organize content and UI in the Panel. A section can display fields, pages lists, files lists, stats, info text, etc.

Sections live inside blueprints and are defined by `type` (e.g. `pages`, `files`, `fields`, …). Kirby also supports custom section types via plugins.

## In prompts (what it usually implies)

- “Add a pages/files list to the Panel” means: add a `pages` or `files` section to the blueprint.
- “Split drafts/unlisted/listed in the Panel” means: multiple `pages` sections with different `status` filters.
- “Custom section” means: plugin development (register a section extension).

## Variants / aliases

- Built-in section catalog:
  - `kirby://sections`
  - `kirby://section/{type}`
- Custom sections via plugin extension: `sections`

## Example

```yaml
sections:
  drafts:
    type: pages
    status: draft
  published:
    type: pages
    status: listed
```

## MCP: Inspect/verify

- Read the resolved blueprint via `kirby_blueprint_read` (or `kirby://blueprint/{encodedId}`).
- Discover available built-in section types via `kirby://sections` and drill into details with `kirby://section/{type}`.
- If a project uses custom sections, inspect plugins with `kirby_plugins_index` and consult `kirby://extension/sections`.

## Related terms

- kirby://glossary/panel
- kirby://glossary/blueprint
- kirby://glossary/extends
- kirby://glossary/query-language

## Links

- https://getkirby.com/docs/reference/panel/sections
- https://getkirby.com/docs/guide/blueprints/layout
- https://getkirby.com/docs/reference/plugins/extensions/sections
