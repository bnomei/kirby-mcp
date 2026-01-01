# Time field (type: time)

## Field summary

Time picker stored as a string (default format HH:MM:SS).

## Storage format

```yaml
time: 14:30:00
```

## Runtime value

`$page->time()->value()` returns the time string; parse as needed.

## Update payload (kirby_update_page_content)

```json
{ "time": "14:30:00" }
```

## Merge strategy

Replace the time string. For adjustments, read, parse, modify, then update.

## Edge cases

The `format` option changes the stored value. Keep payloads in the configured format.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/time`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/time
