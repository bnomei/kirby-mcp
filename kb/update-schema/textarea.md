# Textarea field (type: textarea)

## Field summary

Multi-line text input stored as a plain string with newlines preserved.

## Storage format

```yaml
textarea: "Line 1\nLine 2"
```

## Runtime value

`$page->textarea()->value()` returns a string (may include newlines).

## Update payload (kirby_update_page_content)

```json
{ "textarea": "Line 1
Line 2" }
```

## Merge strategy

Replace the full string. For append/prepend, read the current value and concatenate before updating.

## Edge cases

If editing content files manually, ensure multiline values stay intact. Empty string clears the field.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/textarea`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/textarea
