# Kirby 5 legacy: pages picker field (type: pages)

For Kirby 6 use `pagepicker` and `kirby://field/pagepicker/update-schema`. Keep the stored field key and references; only the picker type changes. A Kirby 5 **pages section** instead becomes `pagelist`, which stores no value and must not receive a content update. The historical storage notes below describe the picker, not the section.

## Field summary

Page picker stored as page references (UUID by default; use `store` to switch to ids).

## Storage format

```yaml
related: page://aBc123XyZ
# or
related:
  - page://aBc123XyZ
  - page://dEf456UvW

# store: id
related:
  - notes/my-first-note
  - notes/another-note
```

## Runtime value

Use `$page->related()->toPage()` for single or `->toPages()` for multiple. These methods resolve UUIDs or ids.

## Update payload (kirby_update_page_content)

```json
{ "related": ["page://aBc123XyZ", "page://dEf456UvW"] }
```

## Merge strategy

Read existing references, merge unique values, then write back using the same store format (uuid or id).

## Edge cases

With `store: id`, values are page ids like `notes/my-first-note`. Keep `multiple` in sync with payload shape. If UUIDs look missing or unexpected, check `content.uuid` config (UUID generation only affects newly created models).

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/pages`.
- Check UUID generation settings via `kirby://config/content.uuid`.
- Resolve an existing target page and use its actual reference; generating a UUID alone does not create a relationship target.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content
- kirby://glossary/pages
- kirby://glossary/uuid

## Links

- https://getkirby.com/docs/reference/panel/fields/pages
- https://getkirby.com/docs/guide/uuids
