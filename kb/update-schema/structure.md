# Structure field (type: structure)

## Field summary

Repeatable entries stored as a YAML list of objects.

## Storage format

```yaml
team:
  - name: John Doe
    role: Developer
  - name: Jane Smith
    role: Designer
```

## Runtime value

Use `$page->team()->toStructure()` for a `Structure` collection or `->yaml()` for arrays.

## Update payload (kirby_update_page_content)

```json
{ "team": [{ "name": "John Doe", "role": "Developer" }] }
```

## Merge strategy

Read existing rows, merge/append in array form, then update the full list.

## Edge cases

Rows have no built-in id; add your own `id` field if you need stable merges.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/structure`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content
- kirby://glossary/structure-field
- kirby://glossary/yaml

## Links

- https://getkirby.com/docs/reference/panel/fields/structure
