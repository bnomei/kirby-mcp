# Range field (type: range)

## Field summary

Slider input stored as a numeric string.

## Storage format

```yaml
range: 50
```

## Runtime value

`$page->range()->value()` returns a string; cast as needed.

## Update payload (kirby_update_page_content)

```json
{ "range": 50 }
```

## Merge strategy

Replace the numeric value. For increments, read the current value, compute, then update.

## Edge cases

Panel validation enforces min/max/step options.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/range`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/range
