# Date field (type: date)

## Field summary

Date picker stored as a string (default format YYYY-MM-DD).

## Storage format

At the top level of a Kirby content text file:

```text
Date: 2025-12-19
```

Inside serialized YAML (for example a structure row), quote the date string:

```yaml
date: '2025-12-19'
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

Panel `display` controls input parsing and can contain bracketed literal text, such as `D. MMMM [in the year] YYYY`. It is separate from storage `format`. Month names work in English and the current Panel language; invalid input is retained and marked invalid. In nested YAML, quote date strings so Symfony YAML does not convert them to timestamps.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/date`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/date
