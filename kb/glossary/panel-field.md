# Panel field (aliases: "custom field", "field plugin")

## Meaning

A Panel field is a built-in or plugin blueprint field type. In Kirby 6 its PHP class extends `Kirby\Form\Field\DisplayField` (no stored value), `ValueField` (value without input) or `InputField` (editor input). Options use typed `#[Prop]` properties/getters; Vue 3 renders the UI. Kirby 5 legacy array definitions must be rewritten as classes, not just rebuilt in JavaScript. See `kirby://kb/panel/reference-fields` for the class and registration pattern.

## In prompts (what it usually implies)

- "Create a custom Panel field" means register a new field type and use it in a blueprint.

## Variants / aliases

- field type name used in the blueprint
- `fields` plugin extension

## Example

```yaml
fields:
  doi:
    type: doi
```

## MCP: Inspect/verify

- `kirby_blueprints_index` + `kirby_blueprint_read`
- `kirby://extension/fields`
- `kirby://field/{type}`
- `kirby://kb/panel/reference-fields`

## Related terms

- kirby://glossary/panel
- kirby://glossary/blueprint
- kirby://glossary/field
- kirby://glossary/plugin

## Links

- https://getkirby.com/docs/reference/plugins/extensions/fields
- https://getkirby.com/docs/reference/panel/fields
