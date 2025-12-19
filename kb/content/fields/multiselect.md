# Multiselect field (type: multiselect)

## Field summary

Multi-select dropdown stored as a list of option keys.

## Storage format

```yaml
multiselect: option1, option2
```

## Runtime value

Use `$page->multiselect()->split()` for comma-separated lists or `->yaml()` if stored as YAML.

## Update payload (kirby_update_page_content)

```json
{ "multiselect": ["option1", "option2"] }
```

## Merge strategy

Read existing values, merge unique items, then write back as array or comma-separated string.

## Edge cases

Ordering is preserved by the stored list; keep a stable order in updates.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/multiselect`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/multiselect
