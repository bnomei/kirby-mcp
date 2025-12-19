# File (aliases: `$file`, `Kirby\Cms\File`, `Kirby\Cms\Image`)

## Meaning

A Kirby “file” is a media item that belongs to a model (page/site/user), like an image, PDF, video, etc. In templates/snippets/controllers, you often handle a single file as `$file` (or `$image` when working with images).

Files have methods for URLs, metadata, type checks, and image-specific transformations (resize, crop, …) when the file is an image.

## In prompts (what it usually implies)

- “Cover image” usually means: `$page->image()` or `$page->files()->template('cover')->first()`.
- “Get file URL” means: `$file->url()` (but only after checking the file exists).
- “Resize image” means: ensure you have an image file object (not null), then call image methods like `resize()`.

## Variants / aliases

- `$file` / `$image`
- `$page->file('document.pdf')`, `$page->image('cover.jpg')`, `$page->files()`
- `$site->files()`, `$user->files()`
- Classes: `Kirby\Cms\File`, `Kirby\Cms\Image`
- Custom extensions: “file methods” via plugins

## Example

```php
<?php if ($image = $page->image('cover.jpg')): ?>
  <img src="<?= $image->resize(800)->url() ?>" alt="">
<?php endif ?>
```

## MCP: Inspect/verify

- Use `kirby_read_page_content` to inspect file-related fields (e.g. a `cover` field may store a filename/uuid).
- Use `kirby_eval` to list files for a page if you need runtime truth:
  - example: `return $page->files()->pluck('filename');`
- When you change templates/snippets that output files, validate with `kirby_render_page`.
- For any specific `$file->method()`, use `kirby_online` with “$file <method>”.

## Related terms

- kirby://glossary/files
- kirby://glossary/page
- kirby://glossary/field
- kirby://glossary/id
- kirby://glossary/uuid
- kirby://glossary/thumb
- kirby://glossary/media
- kirby://glossary/template

## Links

- https://getkirby.com/docs/reference/objects/cms/file
- https://getkirby.com/docs/reference/templates/helpers/image
- https://getkirby.com/docs/guide/files
- https://getkirby.com/docs/reference/plugins/extensions/file-methods
