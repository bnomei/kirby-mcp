# Scenario: Add pagination to a listing (pages/files/search results)

## Goal

Split long lists into pages using Kirby’s pagination, and render navigation links.

## Inputs to ask for

- What you’re paginating (children pages, files, search results, etc.)
- Sort order (e.g. newest first via `flip()`)
- Items per page (`limit`)
- Desired pagination UI (prev/next only vs numbered range)

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Find templates/controllers/snippets: `kirby_templates_index`, `kirby_controllers_index`, `kirby_snippets_index`
- Render and inspect: `kirby_render_page`

## Implementation steps

1. Move pagination logic into a controller if the template is getting noisy.
2. Apply `->paginate($limit)` to a collection and keep a handle to the pagination object.
3. Render navigation only when `hasPages()` is true.

## Examples (template-only)

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php foreach ($articles = $page->children()->listed()->flip()->paginate(10) as $article): ?>
  <article>
    <h2><a href="<?= $article->url() ?>"><?= $article->title()->html() ?></a></h2>
  </article>
<?php endforeach ?>

<?php if ($articles->pagination()->hasPages()): ?>
  <nav class="pagination">
    <?php if ($articles->pagination()->hasNextPage()): ?>
      <a href="<?= $articles->pagination()->nextPageURL() ?>">‹ older posts</a>
    <?php endif ?>
    <?php if ($articles->pagination()->hasPrevPage()): ?>
      <a href="<?= $articles->pagination()->prevPageURL() ?>">newer posts ›</a>
    <?php endif ?>
  </nav>
<?php endif ?>
```

Numbered pagination (range):

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php $pagination = $articles->pagination() ?>
<?php foreach ($pagination->range(10) as $r): ?>
  <a<?= $pagination->page() === $r ? ' aria-current="page"' : '' ?> href="<?= $pagination->pageURL($r) ?>">
    <?= $r ?>
  </a>
<?php endforeach ?>
```

## Verification

- Render page 2+ by visiting a pagination URL like `.../page:2` (Kirby generates these in `nextPageURL()`/`prevPageURL()`).
- Use `kirby_render_page` on a page id that uses the listing template and confirm no errors.

## Glossary quick refs

- kirby://glossary/pagination
- kirby://glossary/template
- kirby://glossary/controller
- kirby://glossary/roots

## Links

- Cookbook: Pagination: https://getkirby.com/docs/cookbook/navigation/pagination
- Cookbook: Paginating posts: https://getkirby.com/docs/cookbook/content-structure/paginating-posts
- Guide: Pagination: https://getkirby.com/docs/guide/templates/pagination
- Reference: `$pages->paginate()`: https://getkirby.com/docs/reference/objects/pages/paginate
