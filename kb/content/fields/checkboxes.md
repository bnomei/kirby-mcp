# Checkboxes field (type: checkboxes)

## Field summary

Multiple-choice checkboxes stored as a list of option keys.

## Storage format

```yaml
checkboxes: option1, option2
```

## Runtime value

Use `$page->checkboxes()->split()` for comma-separated lists or `->yaml()` if stored as YAML.

## Update payload (kirby_update_page_content)

```json
{ "checkboxes": ["option1", "option2"] }
```

## Merge strategy

Read existing values, merge unique items, then write back as array or comma-separated string.

## Edge cases

The `separator` option changes how lists are stored when serialized as strings.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/checkboxes`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/checkboxes
