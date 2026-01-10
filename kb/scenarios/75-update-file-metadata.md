# Scenario: Update file metadata programmatically (`$file->update()`)

## Goal

Batch-update file metadata (e.g. assign a file template) across:

- a single page
- an entire section (`$page->index()->files()`)
- all languages (multilang)

## Inputs to ask for

- Which files to target (by parent, type, extension, query)
- Which metadata to set (`template`, custom fields, captions)
- Whether it’s a multi-language site (update all languages or only default)
- Whether to validate against blueprint rules (`$file->update(..., validate: true)`)

## Internal tools/resources to use

- Confirm roots: `kirby://roots`
- Inspect blueprint for file fields/templates: `kirby_blueprints_index`
- Use safe write tools when possible:
  - `kirby_update_page_content` for page fields
  - `kirby_read_file_content` + `kirby_update_file_content` for file metadata
  - field storage guides: `kirby://fields/update-schema` and `kirby://field/files/update-schema`
  - for batch updates across many files, prefer a Kirby script/route if tool calls become too large

## Implementation steps

1. Authenticate/impersonate (file updates require authentication).
2. For single-file edits, use `kirby_read_file_content` + `kirby_update_file_content` (confirm=true).
3. For batch updates, loop through the target files and call `$file->update([...])`.
4. In multi-language setups, pass the language code as second parameter.
5. Capture errors per file to avoid partial silent failure.
6. Remember `$file->update()` returns a new File object (immutability).

## Examples (quicktip; abridged)

```php
$kirby->impersonate('kirby');

foreach ($page->files() as $file) {
  $file->update([
    'template' => 'gallery-image',
  ]);
}
```

## Verification

- Confirm the file metadata text files are updated (template + fields).
- In multilang sites, confirm each language metadata is updated as intended.

## Glossary quick refs

- kirby://glossary/file
- kirby://glossary/language
- kirby://glossary/template
- kirby://glossary/field

## Links

- Quicktip: Update file metadata: https://getkirby.com/docs/quicktips/update-file-metadata
- Guide: File metadata: https://getkirby.com/docs/guide/content/files#adding-meta-data-to-your-files
- Reference: `$file->update()`: https://getkirby.com/docs/reference/objects/cms/file/update
