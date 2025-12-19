# Color field (type: color)

## Field summary

Color input stored as a plain string (hex or RGB).

## Storage format

```yaml
color: '#ff0000'
```

## Runtime value

`$page->color()->value()` returns the stored color string.

## Update payload (kirby_update_page_content)

```json
{ "color": "#ff0000" }
```

## Merge strategy

Replace the full string. For append/prepend, read the current value and concatenate before updating.

## Edge cases

Panel may output hex or rgb() depending on options.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/color`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/color
