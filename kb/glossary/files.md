# Files (aliases: `$files`, `Kirby\Cms\Files`)

## Meaning

“Files” refers to a **collection of file objects** (`Kirby\Cms\Files`). You usually get a files collection from a page/site/user (`$page->files()`, `$site->files()`, `$user->files()`), then filter/sort/select from it.

## In prompts (what it usually implies)

- “Loop files/images” means iterating a `Files` collection.
- “First image” means `$files->first()` or `$page->images()->first()`.
- “Sort by Panel order” often means `sortBy('sort')` (manual file sort order).

## Variants / aliases

- `$files` (collection)
- `$page->files()`, `$page->images()`, `$site->files()`, `$user->files()`
- Class: `Kirby\Cms\Files`
- Custom extensions: “files methods” via plugins

## Example

```php
<?php foreach ($page->images()->sortBy('sort') as $image): ?>
  <img src="<?= $image->resize(600)->url() ?>" alt="">
<?php endforeach ?>
```

## MCP: Inspect/verify

- Use `kirby_eval` to inspect what a page actually has (ids, filenames, templates):
  - example: `return $page->files()->map(fn ($f) => [$f->filename(), $f->template()])->values();`
- Validate output and thumbnails with `kirby_render_page` (image URLs/media paths are easiest to catch in rendered HTML).
- Look up collection methods via `kirby_online` (“$files filterBy”, “$files sortBy”, etc.).

## Related terms

- kirby://glossary/file
- kirby://glossary/page
- kirby://glossary/field

## Links

- https://getkirby.com/docs/reference/objects/cms/files
- https://getkirby.com/docs/reference/plugins/extensions/files-methods
