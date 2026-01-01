# Blueprint: page (content update)

## Blueprint summary

Page blueprints define the editable content fields for pages. Updates map to `$page->update($data, $language, $validate)`.

## Storage format

Page content is stored in a content file, e.g.:

```yaml
title: About
intro: Short summary text
```

## Runtime value

`$page->content($language)->toArray()` returns a field-value map (strings or serialized values for complex fields).

## Update payload (kirby_update_page_content)

```json
{ "title": "About", "intro": "Short summary text" }
```

## Merge strategy

Replace field values by key. For merges/append, read existing values, merge in memory, then update.

## Edge cases

- Page id vs uuid: tools accept `page://<uuid>` or a bare UUID without the prefix.
- Drafts/changes: read via `kirby_read_page_content` to get the current version before updating.
- Complex fields (blocks/layout/structure) require their own update schemas.

## MCP: Inspect/verify

- Read content: `kirby_read_page_content` or `kirby://page/content/{encodedIdOrUuid}`
- Read blueprint config: `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`
- Field storage guidance: `kirby://fields/update-schema`, `kirby://field/{type}/update-schema`

## Glossary quick refs

- kirby://glossary/page
- kirby://glossary/content
- kirby://glossary/field
- kirby://glossary/uuid

## Links

- https://getkirby.com/docs/reference/panel/blueprints/page
