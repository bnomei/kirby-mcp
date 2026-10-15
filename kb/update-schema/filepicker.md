# File picker field (type: filepicker)

## Field summary

Kirby 6 value field for selected file references. The Kirby 5 legacy name `files` is a deprecated picker alias; a former **files section** instead becomes the non-value `filelist` field. Do not write content for a `filelist`.

## Storage and runtime value

The default stores a YAML list of file UUID references. With `store: id`, files belonging to the current model use filenames; other files use full IDs. Disabling UUIDs forces ID storage in beta.1. Resolve references in the model's context. Renaming the field type does not rename the content key or upload a file.

```yaml
gallery:
  - file://8RxIAFzJekgWfpFn
  - file://mHEVVr6xtDc3gIip
```

Use `$page->gallery()->toFiles()` or `->toFile()` to resolve references. Select actual existing files; a generated UUID alone is not an upload or valid file reference.

## Update payload (kirby_update_page_content)

```json
{ "gallery": ["file://8RxIAFzJekgWfpFn", "file://mHEVVr6xtDc3gIip"] }
```

Read existing references, merge without duplicates if requested, preserve order and storage format, then preview before applying. Respect `multiple`, `min`, `max` and candidate constraints; a single picker uses at most one reference. Test cross-page references and missing files. Verify the resolved files after saving, not just the serialized strings.

## MCP: Inspect/verify

- `kirby://field/filepicker`
- `kirby_blueprint_read` for raw configuration; runtime model schemas for normalized types and `hasValue`.
- `kirby_read_page_content` before and after the update.
- `kirby://config/content.uuid` for UUID generation settings.

## Links

- https://getkirby.com/releases/6/blueprints
- https://getkirby.com/docs/guide/uuids
