# Slug field (type: slug)

## Field summary

Slug input stored as a URL-safe string.

## Storage format

```yaml
slug: my-page-slug
```

## Runtime value

`$page->slug()->value()` returns the stored slug string.

## Update payload (kirby_update_page_content)

```json
{ "slug": "my-page-slug" }
```

## Merge strategy

Replace the full string. For slug updates, set the final value directly.

## Edge cases

The Panel may auto-slugify input and enforce uniqueness depending on context.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/slug`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content
- kirby://glossary/slug

## Links

- https://getkirby.com/docs/reference/panel/fields/slug
