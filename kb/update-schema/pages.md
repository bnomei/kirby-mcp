# Pages field (type: pages)

## Field summary

Page picker stored as page references (uuid by default, id when configured).

## Storage format

```yaml
related: page://aBc123XyZ
# or
related:
  - page://aBc123XyZ
  - page://dEf456UvW
```

## Runtime value

Use `$page->related()->toPage()` for single or `->toPages()` for multiple.

## Update payload (kirby_update_page_content)

```json
{ "related": ["page://aBc123XyZ", "page://dEf456UvW"] }
```

## Merge strategy

Read existing references, merge unique values, then write back using the same store format (uuid or id).

## Edge cases

With `store: id`, values are page ids like `notes/my-first-note`. Keep `multiple` in sync with payload shape.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/pages`.
- If you need a fresh reference, generate a UUID via `kirby://uuid/new` and prefix with `page://`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content
- kirby://glossary/pages
- kirby://glossary/uuid

## Links

- https://getkirby.com/docs/reference/panel/fields/pages
