# Entries field (type: entries)

## Field summary

Mixed-content picker stored as a YAML list; item shape depends on configured entry types (pages/files/users/urls/custom).

## Storage format

```yaml
entries:
  - type: page
    value: page://aBc123XyZ
  - type: file
    value: file://8RxIAFzJekgWfpFn
```

## Runtime value

Use `$page->entries()->yaml()` to inspect the raw list of entries.

## Update payload (kirby_update_page_content)

```json
{ "entries": [{ "type": "page", "value": "page://aBc123XyZ" }] }
```

## Merge strategy

Read existing entries, merge by your own rules (type/value), then update the full list.

## Edge cases

Entry payload shape varies by entry config; verify in the content lab before writing. For picker-based entries, `value` often uses UUIDs unless configured otherwise.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/entries`.
- Check UUID generation settings via `kirby://config/content.uuid`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/entries
- https://getkirby.com/docs/guide/uuids
