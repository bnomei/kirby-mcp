# Files field (type: files)

## Field summary

File picker stored as file references (uuid by default, filename or path when configured).

## Storage format

```yaml
gallery:
  - file://8RxIAFzJekgWfpFn
  - file://mHEVVr6xtDc3gIip
```

## Runtime value

Use `$page->gallery()->toFiles()` for multiple or `->toFile()` for single.

## Update payload (kirby_update_page_content)

```json
{ "gallery": ["file://8RxIAFzJekgWfpFn", "file://mHEVVr6xtDc3gIip"] }
```

## Merge strategy

Read existing references, merge unique values, then write back using the same store format (uuid or id).

## Edge cases

With `store: id`, values are filenames or paths. Cross-page file references may use `parent/filename`.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/files`.
- If you need a fresh reference, generate a UUID via `kirby://uuid/new` and prefix with `file://`.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content
- kirby://glossary/files
- kirby://glossary/uuid

## Links

- https://getkirby.com/docs/reference/panel/fields/files
