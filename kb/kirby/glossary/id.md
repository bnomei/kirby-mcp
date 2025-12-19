# ID (aliases: `$page->id()`, `$file->id()`, `page('blog/post')`, `blog%2Fpost`)

## Meaning

In Kirby, an “id” is the internal identifier used to find and reference models. For pages, the id is the **path in the site tree** (e.g. `blog/post`). For files, the id is also a string identifier (usually scoped to its parent model).

The important distinction in multi-language setups: a page’s `id()` is *not translated*, while the page’s `uri()` can be translated.

## In prompts (what it usually implies)

- “Find page by id `blog/post`” means: use `page('blog/post')` / `$site->find('blog/post')` and confirm the page exists.
- “Encoded id” usually refers to URL-encoding ids with slashes when used in URLs (e.g. resource templates): `blog/post` → `blog%2Fpost`.
- “Use uuid instead of id” means: use a global identifier (`uuid`) that doesn’t change when moving content.

## Variants / aliases

- `$page->id()` (page id, like `parent/child`)
- `$file->id()` (file id)
- Related identifiers:
  - `$page->uri()` (translated id)
  - `$page->uid()` / `$page->slug()` (single segment)
  - `$page->uuid()` (global id)

## Example

```php
<?php

$page = page('blog/post');
echo $page?->id();
```

## MCP: Inspect/verify

- When you need actual content for an id, prefer `kirby_read_page_content` (or `kirby://page/content/{encodedIdOrUuid}`).
  - If your id contains slashes, URL-encode it for the resource template: `blog/post` → `blog%2Fpost`.
- Use `kirby_eval` to confirm what Kirby thinks the id is:
  - example: `return page('blog/post')?->id();`

## Related terms

- kirby://glossary/page
- kirby://glossary/file
- kirby://glossary/uuid
- kirby://glossary/uri
- kirby://glossary/slug
- kirby://glossary/uid

## Links

- https://getkirby.com/docs/reference/objects/cms/page/id
- https://getkirby.com/docs/reference/objects/cms/file/id
- https://getkirby.com/docs/reference/templates/helpers/page

