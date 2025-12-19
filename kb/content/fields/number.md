# Number field (type: number)

## Field summary

Numeric input stored as a string in the content file.

## Storage format

```yaml
number: 42
```

## Runtime value

`$page->number()->value()` returns a string; cast or use field methods like `toInt()` or `toFloat()`.

## Update payload (kirby_update_page_content)

```json
{ "number": 42 }
```

## Merge strategy

Replace the numeric value. For increments, read the current value, compute, then update.

## Edge cases

Panel validation enforces min/max/step. Use dot decimals (e.g., 3.14).

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/number`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/number
