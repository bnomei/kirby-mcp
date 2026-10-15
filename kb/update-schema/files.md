# Kirby 5 legacy: files picker field (type: files)

For Kirby 6 use `filepicker` and `kirby://field/filepicker/update-schema`. Keep the stored field key and references; only the picker type changes. A Kirby 5 **files section** instead becomes `filelist`, which stores no value and must not receive a content update. The historical storage notes below describe the picker, not the section.

## Field summary

File picker stored as file references (UUID by default; use `store` for filenames/paths).

## Storage format

```yaml
gallery:
  - file://8RxIAFzJekgWfpFn
  - file://mHEVVr6xtDc3gIip

# store: id (filename or path)
gallery:
  - image-1.jpg
  - photography/albums/trees/tree.jpg
```

## Runtime value

Use `$page->gallery()->toFiles()` for multiple or `->toFile()` for single. These methods resolve UUIDs or ids.

## Update payload (kirby_update_page_content)

```json
{ "gallery": ["file://8RxIAFzJekgWfpFn", "file://mHEVVr6xtDc3gIip"] }
```

## Merge strategy

Read existing references, merge unique values, then write back using the same store format (uuid or id).

## Edge cases

With `store: id`, values are filenames or paths. Cross-page file references may use `parent/filename`. If UUIDs look missing or unexpected, check `content.uuid` config (UUID generation only affects newly created models).

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/files`.
- Check UUID generation settings via `kirby://config/content.uuid`.
- Resolve an existing file and use its actual reference; generating a UUID alone does not upload or create a file.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content
- kirby://glossary/files
- kirby://glossary/uuid

## Links

- https://getkirby.com/docs/reference/panel/fields/files
- https://getkirby.com/docs/guide/uuids
