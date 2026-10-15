# Entries field (type: entries)

## Field summary

Repeats one configured field, producing a list of values. It is a simpler alternative to a structure field for single-field rows, not a mixed page/file/user picker. Configure the repeated input with `field: { type: text }` (text is the default).

## Storage format

For a text input, the content field contains a YAML list:

```text
Entries:

- Design
- Architecture
```

## Runtime value

Use `$page->entries()->yaml()` for the raw list, or `$page->entries()->toEntries()` to iterate values as field objects (e.g. `$entry->escape()`).

## Update payload (kirby_update_page_content)

```json
{ "entries": ["Design", "Architecture"] }
```

## Merge strategy

Read the existing list, append/remove/reorder values as needed, then replace the full list. Preserve order and intentional duplicates.

## Edge cases

Supported repeated field types are `color`, `date`, `email`, `number`, `select`, `slug`, `tel`, `text`, `time`, and `url`. Values follow the configured field's storage rules; respect its validation and the entries field's `min`/`max` limits.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/entries`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/entries
