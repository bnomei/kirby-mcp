# Tags field (type: tags)

## Field summary

Tag input stored as a list of strings (comma-separated by default).

## Storage format

```yaml
tags: design, code, kirby
```

## Runtime value

Use `$page->tags()->split()` for comma-separated lists or `->yaml()` if stored as YAML.

## Update payload (kirby_update_page_content)

```json
{ "tags": ["design", "code", "kirby"] }
```

## Merge strategy

Read existing tags, merge unique items, then write back as array or comma-separated string.

## Edge cases

The `separator` option controls string storage. Tags may be normalized to lowercase.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/tags`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/tags
