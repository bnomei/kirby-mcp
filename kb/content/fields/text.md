# Text field (type: text)

## Field summary

Single-line text input stored as a plain string.

## Storage format

```yaml
text: Hello World
```

## Runtime value

`$page->text()->value()` returns a string. Use `->escape()` before output.

## Update payload (kirby_update_page_content)

```json
{ "text": "Hello World" }
```

## Merge strategy

Replace the full string. For append/prepend, read the current value and concatenate before updating.

## Edge cases

Panel may normalize whitespace. Empty string clears the field; `null` removes the key on save.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/text`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/text
