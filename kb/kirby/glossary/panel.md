# Panel (aliases: “Kirby Panel”, “admin UI”)

## Meaning

The Panel is Kirby’s web interface for managing content. It is configured primarily via [blueprints](kirby://glossary/blueprint) (fields/sections/tabs, permissions, allowed page types, etc.).

If a prompt mentions “Panel fields”, “sections”, “blocks”, “page create dialog”, etc., you are usually dealing with blueprint configuration and Panel reference docs.

## In prompts (what it usually implies)

- “Add a field in the Panel” means: update the relevant blueprint.
- “Pages section doesn’t show X” means: check the blueprint’s section config and the current page type/status.
- “Custom Panel UI” may mean: plugin development (custom fields/sections/areas).

## Variants / aliases

- Panel fields (types + options): `kirby://fields` + `kirby://field/{type}`
- Panel sections (types + options): `kirby://sections` + `kirby://section/{type}`
- Blueprint parts: fields/sections/tabs mixins

## Example

```yaml
tabs:
  content:
    sections:
      pages:
        type: pages
        status: listed
```

## MCP: Inspect/verify

- Identify which blueprint applies via `kirby_blueprints_index` and `kirby_blueprint_read` (or `kirby://blueprint/{encodedId}`).
- Use `kirby://fields` and `kirby://sections` to discover available types and drill into details with `kirby://field/{type}` / `kirby://section/{type}`.
- If the Panel behavior is unclear, use `kirby_online` for the exact feature (“page creation dialog”, “pages section status”, etc.).

## Related terms

- kirby://glossary/blueprint
- kirby://glossary/extends
- kirby://glossary/query-language
- kirby://glossary/plugin

## Links

- https://getkirby.com/docs/guide/tour/#the-panel
- https://getkirby.com/docs/guide/blueprints/introduction
- https://getkirby.com/docs/reference/panel/fields
- https://getkirby.com/docs/reference/panel/sections
