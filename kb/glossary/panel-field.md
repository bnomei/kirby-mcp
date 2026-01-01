# Panel field (aliases: "custom field", "field plugin")

## Meaning

A Panel field is a blueprint field type backed by a plugin. It defines PHP props and a Vue component used in the Panel.

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
