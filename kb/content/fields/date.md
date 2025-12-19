# Date field (type: date)

## Field summary

Date picker stored as a string (default format YYYY-MM-DD).

## Storage format

```yaml
date: 2025-12-19
```

## Runtime value

`$page->date()->toDate()` parses the string into a timestamp; use `toDate('Y-m-d')` for formatting.

## Update payload (kirby_update_page_content)

```json
{ "date": "2025-12-19" }
```

## Merge strategy

Replace the date string. For adjustments, read, parse, modify, then update.

## Edge cases

The `format` option changes the stored value. Keep payloads in the configured format.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/date`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/date
