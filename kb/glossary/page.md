# Page (aliases: `$page`, `Kirby\Cms\Page`, `page()`)

## Meaning

In Kirby, a “page” is a node in the site tree. It is often backed by a folder in the `content` root and typically has fields (content), files, children, status, a template, and (optionally) a controller and page model.

In templates/snippets/controllers, `$page` is the **currently rendered page object**.

## In prompts (what it usually implies)

- “Use `$page->…`” means: you are in a context that has the current page (template/snippet/controller), or you need to fetch a page object first.
- “Create a page” can mean:
  - create content folders/files (filesystem), or
  - create programmatically (`$page->createChild()`), which requires permissions/auth.
- “Render page as JSON” often means “content representation” (e.g. `blog.json`) rather than “Kirby API”.

## Variants / aliases

- `$page` (current page in templates/snippets/controllers)
- `page()` helper (fetch a page by id or get the current page; published pages only)
- `$kirby->page('…')` (can fetch drafts as well)
- `$site->find('blog')`, `$site->index()->findBy(...)`
- Class: `Kirby\Cms\Page`
- `$page->children()` returns a [pages collection](kirby://glossary/pages)
- `$page->title()`, `$page->text()` return a kirby://glossary/field

## Example

```php
<article>
  <h1><?= $page->title()->escape() ?></h1>
  <?= $page->text()->kt() ?>
</article>
```

## MCP: Inspect/verify

- Resolve paths with `kirby_roots` first (don’t assume `content/` or `site/`).
- If you need actual project data for a given page, use `kirby_read_page_content` (or `kirby://page/content/{encodedIdOrUuid}`).
  - For nested ids like `blog/post`, URL-encode the id for the resource template: `blog%2Fpost`.
- To verify rendering/output (HTML or representations like JSON), use `kirby_render_page` (install runtime commands with `kirby_runtime_install` if prompted).
- For quick runtime checks, use `kirby_eval` (e.g. `return page('blog')->id();`).

## Related terms

- kirby://glossary/site
- kirby://glossary/pages
- kirby://glossary/field
- kirby://glossary/file
- kirby://glossary/id
- kirby://glossary/uid
- kirby://glossary/slug
- kirby://glossary/uri
- kirby://glossary/uuid
- kirby://glossary/template
- kirby://glossary/controller
- kirby://glossary/page-model
- kirby://glossary/content-representation

## Links

- https://getkirby.com/docs/guide/content/creating-pages
- https://getkirby.com/docs/reference/objects/cms/page
- https://getkirby.com/docs/reference/templates/helpers/page
