# Link field (type: link)

## Field summary

Stores a single link string, not a YAML object with `type` and `value`. For link text, target, or other metadata, wrap a link field in an `object` field with separate sibling fields.

## Storage format

```text
Cta: https://example.com
```

## Runtime value

`$page->cta()->value()` returns the stored string. Use `$page->cta()->toUrl()` to resolve internal references and other supported link types to a URL.

## Update payload (kirby_update_page_content)

```json
{ "cta": "https://example.com" }
```

## Merge strategy

Replace the link string. If using an object wrapper, preserve its other fields when replacing the object.

## Edge cases

- Internal links can be `page://…` or `file://…` references; do not wrap them in a type/value object.
- The `custom` link type is disabled by default because arbitrary schemes cannot be validated safely. Enable it only with appropriate validation/sanitization for the intended use case.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/link`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content
- kirby://glossary/yaml

## Links

- https://getkirby.com/docs/reference/panel/fields/link
