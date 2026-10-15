# Page picker field (type: pagepicker)

## Field summary

Kirby 6 value field for selected page references. The Kirby 5 legacy name `pages` is a deprecated picker alias; a former **pages section** instead becomes the non-value `pagelist` field. Do not write content for a `pagelist`.

## Storage and runtime value

The default stores a YAML list of page UUID references. With `store: id`, preserve page IDs instead. Disabling UUIDs forces ID storage in beta.1. The field key stays unchanged when renaming its type.

```yaml
related:
  - page://aBc123XyZ
  - page://dEf456UvW
```

Use `$page->related()->toPage()` or `->toPages()` to resolve references. Resolve real target pages first; generating a random UUID does not create a target or a usable relationship.

## Update payload (kirby_update_page_content)

```json
{ "related": ["page://aBc123XyZ", "page://dEf456UvW"] }
```

Read existing references, merge without duplicates if requested, preserve order and storage format, then preview before applying. Respect `multiple`, `min`, `max` and candidate constraints. For a single picker, use a list with at most one reference. Re-read content and resolve each target after saving.

## MCP: Inspect/verify

- `kirby://field/pagepicker`
- `kirby_blueprint_read` for raw configuration; runtime model schemas for normalized types and `hasValue`.
- `kirby_read_page_content` before and after the update.
- `kirby://config/content.uuid` when references are absent (UUID settings affect newly created models).

## Links

- https://getkirby.com/releases/6/blueprints
- https://getkirby.com/docs/guide/uuids
