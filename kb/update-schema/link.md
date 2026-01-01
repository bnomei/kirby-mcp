# Link field (type: link)

## Field summary

Link field stored as a YAML object with type and value (plus optional text/target).

## Storage format

```yaml
cta:
  type: url
  value: https://example.com
  text: Learn more
  target: _blank
```

## Runtime value

Use `$page->cta()->yaml()` to get the structured link data.

## Update payload (kirby_update_page_content)

```json
{ "cta": { "type": "url", "value": "https://example.com", "text": "Learn more", "target": "_blank" } }
```

## Merge strategy

Replace the full link object. If you only need to change one key, read, update, and write back.

## Edge cases

For type `page` or `file`, the value is a stored reference (uuid or id).

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/link`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content
- kirby://glossary/yaml

## Links

- https://getkirby.com/docs/reference/panel/fields/link
