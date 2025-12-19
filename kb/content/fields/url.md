# Url field (type: url)

## Field summary

URL input stored as a plain string.

## Storage format

```yaml
url: https://example.com
```

## Runtime value

`$page->url()->value()` returns the stored URL string.

## Update payload (kirby_update_page_content)

```json
{ "url": "https://example.com" }
```

## Merge strategy

Replace the full string. For append/prepend, read the current value and concatenate before updating.

## Edge cases

Panel validation may normalize or reject invalid URLs.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/url`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content

## Links

- https://getkirby.com/docs/reference/panel/fields/url
