# Toggle field (type: toggle)

## Field summary

On/off switch stored as a boolean-like string (true/false) or custom values.

## Storage format

```yaml
toggle: true
```

## Runtime value

`$page->toggle()->value()` returns the stored string; cast to bool if needed.

## Update payload (kirby_update_page_content)

```json
{ "toggle": true }
```

## Merge strategy

Replace the stored value with true/false or the configured custom values.

## Edge cases

If `text` or custom values are configured, the stored value may differ from true/false.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/toggle`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/toggle
