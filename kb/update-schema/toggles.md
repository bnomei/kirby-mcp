# Toggles field (type: toggles)

## Field summary

Button group stored as a single option key string.

## Storage format

```yaml
toggles: option-key
```

## Runtime value

`$page->toggles()->value()` returns the option key string.

## Update payload (kirby_update_page_content)

```json
{ "toggles": "option-key" }
```

## Merge strategy

Replace the stored option key with the new selection.

## Edge cases

Some configs allow multiple selection; if enabled, store as list and parse accordingly.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/toggles`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/toggles
