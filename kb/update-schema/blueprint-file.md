# Blueprint: file (content update)

## Blueprint summary

File blueprints define metadata fields for files (e.g. caption, alt text). Updates map to `$file->update($data, $language, $validate)`.

## Storage format

File metadata lives next to the file, e.g.:

```yaml
alt: Cover image
caption: Short description
```

## Runtime value

`$file->content($language)->toArray()` returns a field-value map.

## Update payload (kirby_update_file_content)

```json
{ "alt": "Cover image", "caption": "Short description" }
```

## Merge strategy

Replace field values by key. For merges/append, read existing values, merge in memory, then update.

## Edge cases

- File id vs uuid: use `parent/filename.ext` or a `file://<uuid>` UUID.
- File updates may clear derived media files when `focus` changes.
- Complex fields (blocks/layout/structure) require their own update schemas.

## MCP: Inspect/verify

- Read content: `kirby_read_file_content` or `kirby://file/content/{encodedIdOrUuid}`
- Read blueprint config: `kirby_blueprint_read` or `kirby://blueprint/files%2Fimage`
- Field storage guidance: `kirby://fields/update-schema`, `kirby://field/{type}/update-schema`

## Glossary quick refs

- kirby://glossary/file
- kirby://glossary/files
- kirby://glossary/content
- kirby://glossary/field
- kirby://glossary/uuid

## Links

- https://getkirby.com/docs/reference/panel/blueprints/file
