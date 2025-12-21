# Hidden field (type: hidden)

## Field summary

Hidden input stored as a plain string (often set via blueprint defaults).

## Storage format

```yaml
hidden: internal
```

## Runtime value

`$page->hidden()->value()` returns the stored string.

## Update payload (kirby_update_page_content)

```json
{ "hidden": "internal" }
```

## Merge strategy

Replace the full string. For append/prepend, read the current value and concatenate before updating.

## Edge cases

Hidden fields are not visible in the Panel but still persist in content files.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/hidden`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/hidden
