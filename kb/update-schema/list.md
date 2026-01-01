# List field (type: list)

## Field summary

List input stored as an HTML string representing the list.

## Storage format

```yaml
list: '<ul><li>Item 1</li></ul>'
```

## Runtime value

`$page->list()->value()` returns HTML. Output raw or sanitize as needed.

## Update payload (kirby_update_page_content)

```json
{ "list": "<ul><li>Item 1</li></ul>" }
```

## Merge strategy

Replace the full HTML string. For partial edits, parse the HTML and rebuild before updating.

## Edge cases

Avoid running KirbyText on this field; it is already HTML.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/list`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/list
