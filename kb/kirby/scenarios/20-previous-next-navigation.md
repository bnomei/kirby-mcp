# Scenario: Add previous/next navigation links

## Goal

Add “previous/next” links between pages in a collection (common: blog articles).

## Inputs to ask for

- Which scope to navigate:
  - all siblings
  - listed only / unlisted only
- Sort direction (if you reverse sorting, you may also want to reverse “prev/next” semantics)
- Where links should appear (article template, listing, both)

## Internal tools/resources to use

- Find templates/snippets: `kirby_templates_index`, `kirby_snippets_index`
- Render and inspect: `kirby_render_page`

## Implementation steps

1. Decide which navigation helpers to use (`prev/next` vs `prevListed/nextListed`, etc.).
2. Add guard checks with `hasPrev*()`/`hasNext*()` before rendering links.

## Examples

### Navigate through all pages

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php if ($page->hasPrev()): ?>
  <a href="<?= $page->prev()->url() ?>">previous page</a>
<?php endif ?>

<?php if ($page->hasNext()): ?>
  <a href="<?= $page->next()->url() ?>">next page</a>
<?php endif ?>
```

### Navigate through listed pages only

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php if ($page->hasPrevListed()): ?>
  <a href="<?= $page->prevListed()->url() ?>">previous page</a>
<?php endif ?>

<?php if ($page->hasNextListed()): ?>
  <a href="<?= $page->nextListed()->url() ?>">next page</a>
<?php endif ?>
```

## Verification

- Render a few neighboring pages and confirm the link targets match the intended order.

## Glossary quick refs

- kirby://glossary/template
- kirby://glossary/snippet

## Links

- Cookbook: Previous / Next navigation: https://getkirby.com/docs/cookbook/navigation/previous-next
