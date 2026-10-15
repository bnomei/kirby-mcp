# Panel (aliases: “Kirby Panel”, “admin UI”)

## Meaning

The Panel is Kirby's web interface for managing content. Kirby 6 blueprints define value/display fields, tabs, columns, permissions and allowed page types. The Panel uses Vue 3.

If a prompt mentions “Panel fields”, “sections”, “blocks”, “page create dialog”, etc., you are usually dealing with blueprint configuration and Panel reference docs.

## In prompts (what it usually implies)

- “Add a field in the Panel” means: update the relevant blueprint.
- “Pages section doesn’t show X” is legacy terminology: inspect the Kirby 6 `pagelist` display field and current page type/status.
- “Custom Panel UI” may mean plugin development (PHP field classes and Vue 3 fields/areas).

## Variants / aliases

- Panel fields (types + options): `kirby://fields` + `kirby://field/{type}`
- Kirby 5 legacy section migration references: `kirby://sections`; built-in sections auto-convert, custom sections must be ported to fields.
- Blueprint parts: fields/tabs/columns and reusable mixins

## Example

```yaml
tabs:
  content:
    fields:
      pages:
        type: pagelist
        status: listed
```

## MCP: Inspect/verify

- Identify which blueprint applies via `kirby_blueprints_index` and `kirby_blueprint_read` (or `kirby://blueprint/{encodedId}`).
- Use `kirby://fields` and `kirby://field/{type}` for canonical field types; pickers store values and lists do not.
- If the Panel behavior is unclear, use `kirby_online` for the exact feature (“page creation dialog”, “pages section status”, etc.).

## Related terms

- kirby://glossary/blueprint
- kirby://glossary/extends
- kirby://glossary/kirbyup
- kirby://glossary/kirbyuse
- kirby://glossary/panel-area
- kirby://glossary/panel-field
- kirby://glossary/panel-section
- kirby://glossary/query-language
- kirby://glossary/plugin

## Links

- https://getkirby.com/docs/guide/tour/#the-panel
- https://getkirby.com/docs/guide/blueprints/introduction
- https://getkirby.com/docs/reference/panel/fields
- https://getkirby.com/docs/reference/panel/sections
