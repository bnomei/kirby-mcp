# Pagination (aliases: `$pagination`, `paginate()`, “next/prev page”)

## Meaning

In Kirby, pagination is created by calling `paginate()` on a collection (often a `Pages` collection). The paginated collection then exposes a `$pagination` object that tells you the current page, total pages, and provides URLs for previous/next pages.

## In prompts (what it usually implies)

- “Paginate children” usually means `$page->children()->paginate(n)`.
- “Load more” often means: use a JSON representation + `$pagination->nextPageUrl()` (or a route) to fetch the next slice.
- “Prev/next links” means: use `$pagination->hasPrevPage()`/`hasNextPage()` and the corresponding URLs.

## Variants / aliases

- `$collection = $page->children()->listed()->paginate(10)`
- `$pagination = $collection->pagination()`
- `$pagination->page()` / `pages()` / `total()`
- `$pagination->prevPageUrl()` / `nextPageUrl()`

## Example

```php
<?php
$articles   = page('blog')->children()->listed()->paginate(5);
$pagination = $articles->pagination();
?>

<?php if ($pagination->hasNextPage()): ?>
  <a href="<?= $pagination->nextPageUrl() ?>">Older</a>
<?php endif ?>
```

## MCP: Inspect/verify

- Verify the underlying collection and the pagination object with `kirby_eval`:
  - example: `return page('blog')->children()->listed()->paginate(5)->pagination()->toArray();`
- Render the page with `kirby_render_page` to confirm the generated pagination URLs in real output.

## Related terms

- kirby://glossary/page
- kirby://glossary/pages
- kirby://glossary/route
- kirby://glossary/content-representation

## Links

- https://getkirby.com/docs/reference/objects/cms/pagination
