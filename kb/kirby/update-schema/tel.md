# Tel field (type: tel)

## Field summary

Telephone input stored as a plain string.

## Storage format

```yaml
tel: +1 234 567 890
```

## Runtime value

`$page->tel()->value()` returns the stored phone string.

## Update payload (kirby_update_page_content)

```json
{ "tel": "+1 234 567 890" }
```

## Merge strategy

Replace the full string. For append/prepend, read the current value and concatenate before updating.

## Edge cases

Panel validation may normalize formats depending on configuration.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/tel`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/tel
