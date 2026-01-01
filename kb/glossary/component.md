# Component / core component (aliases: “core components”, “component plugin”)

## Meaning

Kirby’s “component system” lets plugins replace selected parts of the core runtime (e.g. Markdown parsing, thumbnail generation, URL building, template/snippet loading, search). This is powerful, but it’s also a sharp tool: component overrides affect the whole site and can change behavior in surprising ways.

## In prompts (what it usually implies)

- “Override Markdown” / “use a different Markdown parser” means: replace the `markdown` core component.
- “Custom thumbs/transform pipeline” means: replace the `thumb` component.
- “Why do URLs look different?” can mean: a plugin overrides the `url` or `file-urls` component.

## Variants / aliases

- “Core component” (Kirby terminology)
- Implemented via a plugin that registers component overrides
- Component types include: `markdown`, `thumb`, `url`, `template`, `snippet`, `search`, etc.

## Example

When debugging, a common first step is to check whether any plugin is overriding a core component (e.g. `markdown` or `thumb`) before assuming Kirby’s default behavior.

## MCP: Inspect/verify

- List installed plugins with `kirby_plugins_index` and identify anything likely to override components (search/markdown/thumb plugins).
- Consult the official component list via `kirby://extension/core-components` and drill down into specific component docs.
- Use `kirby_eval` to sanity-check runtime behavior (e.g. how `kirby()->markdown()` behaves) before and after changes.

## Related terms

- kirby://glossary/plugin
- kirby://glossary/markdown
- kirby://glossary/cache

## Links

- https://getkirby.com/docs/reference/plugins/extensions/core-components
