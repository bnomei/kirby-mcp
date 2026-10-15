# Select field (type: select)

## Field summary

Single-choice dropdown stored as a string (option key).

## Storage format

```yaml
select: option-key
```

## Runtime value

`$page->select()->value()` returns the option key string.

## Update payload (kirby_update_page_content)

```json
{ "select": "option-key" }
```

## Merge strategy

Replace the stored option key with the new selection.

## Edge cases

If option keys are numeric, they are stored as strings. Labels are not stored.

Kirby 6 preserves invalid option values instead of dropping them on save. Validate and map removed options explicitly. Legacy `options: query`/`options: api` plus separate settings must become nested `options` configuration; see `kirby://glossary/query-language`.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/select`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/select
