# Object field (type: object)

## Field summary

Single object stored as a YAML map of key/value pairs.

## Storage format

```yaml
address:
  street: Main Street 1
  city: New York
  zip: '10001'
```

## Runtime value

Use `$page->address()->yaml()` to get an array of fields.

## Update payload (kirby_update_page_content)

```json
{ "address": { "street": "Main Street 1", "city": "New York", "zip": "10001" } }
```

## Merge strategy

Read existing object, merge keys, then update the full object.

## Edge cases

Nested fields follow the blueprint for the object. Missing keys may be dropped on save.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/object`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content
- kirby://glossary/yaml

## Links

- https://getkirby.com/docs/reference/panel/fields/object
