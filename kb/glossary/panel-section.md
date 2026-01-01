# Panel section

## Meaning

A Panel section is a blueprint UI block for lists or information that is registered via the `sections` plugin extension. Sections load data asynchronously and do not directly store content.

## In prompts (what it usually implies)

- "Custom Panel section" means a plugin-defined section type used in blueprints.

## Variants / aliases

- section type name used in the blueprint
- `sections` plugin extension

## Example

```yaml
sections:
  stats:
    type: stats
```

## MCP: Inspect/verify

- `kirby_blueprints_index` + `kirby_blueprint_read`
- `kirby://extension/sections`
- `kirby://section/{type}`
- `kirby://kb/panel/reference-sections`

## Related terms

- kirby://glossary/panel
- kirby://glossary/blueprint
- kirby://glossary/section
- kirby://glossary/plugin

## Links

- https://getkirby.com/docs/reference/plugins/extensions/sections
- https://getkirby.com/docs/reference/panel/sections
