# Writer field (type: writer)

## Field summary

Rich text editor stored as an HTML string.

## Storage format

```yaml
writer: '<p>Hello <strong>World</strong></p>'
```

## Runtime value

`$page->writer()->value()` returns HTML. Output raw or sanitize as needed.

## Update payload (kirby_update_page_content)

```json
{ "writer": "<p>Hello <strong>World</strong></p>" }
```

## Merge strategy

Replace the full HTML string. For partial edits, parse the HTML and rebuild before updating.

## Edge cases

Writer outputs HTML, not KirbyText. Avoid `->kt()` on this field.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/writer`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/writer
