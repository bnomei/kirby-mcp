# Pages (aliases: `$pages`, `Kirby\Cms\Pages`, `pages()`)

## Meaning

“Pages” usually refers to a **collection of Page objects** (`Kirby\Cms\Pages`). You get a pages collection from many places: the top level (`$pages`), the children of a page (`$page->children()`), the full site index (`$site->index()`), search results, etc.

Pages collections can be filtered, sorted, grouped, paginated, and iterated.

## In prompts (what it usually implies)

- “Loop pages” means iterating a `Pages` collection.
- “Filter/sort pages” means applying collection methods (e.g. `filterBy()`, `sortBy()`, `listed()`).
- “Paginate pages” means calling `paginate()` and then using the resulting `Pagination` object.

## Variants / aliases

- `$pages` (in templates/snippets: top-level pages collection)
- `pages()` helper
- `$page->children()`, `$site->index()`, `$site->find(...)`
- Class: `Kirby\Cms\Pages`
- Custom extensions: “pages methods” via plugins

## Example

```php
<?php foreach ($page->children()->listed()->sortBy('date', 'desc') as $child): ?>
  <a href="<?= $child->url() ?>"><?= $child->title()->escape() ?></a>
<?php endforeach ?>
```

## MCP: Inspect/verify

- Render the relevant page with `kirby_render_page` to validate loops/filters against real content.
- If you need to inspect which pages are in a collection, use `kirby_eval` to return ids/uuids (keep it small):
  - example: `return $page->children()->listed()->pluck('id');`
- If a method is unfamiliar, use `kirby_online` (“$pages filterBy”, “$pages paginate”, etc.).

## Related terms

- kirby://glossary/page
- kirby://glossary/site
- kirby://glossary/pagination
- kirby://glossary/route
- kirby://glossary/content-representation

## Links

- https://getkirby.com/docs/reference/objects/cms/pages
- https://getkirby.com/docs/reference/templates/helpers/pages
- https://getkirby.com/docs/guide/templates/collections
- https://getkirby.com/docs/reference/plugins/extensions/pages-methods
