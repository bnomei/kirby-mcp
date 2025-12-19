# Radio field (type: radio)

## Field summary

Single-choice radio input stored as a string (option key).

## Storage format

```yaml
radio: option-key
```

## Runtime value

`$page->radio()->value()` returns the option key string.

## Update payload (kirby_update_page_content)

```json
{ "radio": "option-key" }
```

## Merge strategy

Replace the stored option key with the new selection.

## Edge cases

Labels are not stored; only the option key is persisted.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/radio`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/radio
