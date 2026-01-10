# Scenario: Build navigation menus (main menu, submenu, breadcrumb)

## Goal

Create reusable navigation snippets for common menu patterns:

- main menu from listed pages
- submenu from a page’s listed children
- breadcrumb navigation

## Inputs to ask for

- Which pages should appear (listed only, specific pages, nested menus)
- Active state behavior (`isOpen()`, `isActive()`)
- Accessibility/markup preferences (e.g. `aria-current`)

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Find existing snippets/templates: `kirby_snippets_index`, `kirby_templates_index`
- Render and inspect: `kirby_render_page`

## Implementation steps

1. Implement menus as snippets (`site/snippets/...`) so templates stay clean.
2. Use `isOpen()` for “active trail” in menus; use `isActive()` for breadcrumb current item.
3. Only render menus when items exist (`isNotEmpty()`).

## Examples

### Main menu (listed root pages)

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php if (($items = $pages->listed())->isNotEmpty()): ?>
  <nav>
    <ul>
      <?php foreach ($items as $item): ?>
        <li><a<?= $item->isOpen() ? ' class="active"' : '' ?> href="<?= $item->url() ?>"><?= $item->title()->html() ?></a></li>
      <?php endforeach ?>
    </ul>
  </nav>
<?php endif ?>
```

### Submenu (children of current menu item)

Assumes `$item` is the open menu item (e.g. from a parent loop or `$pages->findOpen()`).

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php if (($children = $item->children()->listed())->isNotEmpty()): ?>
  <ul>
    <?php foreach ($children as $child): ?>
      <li><a href="<?= $child->url() ?>"><?= $child->title()->html() ?></a></li>
    <?php endforeach ?>
  </ul>
<?php endif ?>
```

### Breadcrumb

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<nav aria-label="breadcrumb">
  <ol>
    <?php foreach ($site->breadcrumb() as $crumb): ?>
      <li<?= $crumb->isActive() ? ' aria-current="page"' : '' ?>>
        <a href="<?= $crumb->url() ?>"><?= $crumb->title()->html() ?></a>
      </li>
    <?php endforeach ?>
  </ol>
</nav>
```

## Verification

- Render multiple pages and confirm active states and breadcrumb trail are correct.

## Glossary quick refs

- kirby://glossary/snippet
- kirby://glossary/roots
- kirby://glossary/template

## Links

- Cookbook: Menus: https://getkirby.com/docs/cookbook/navigation/menus
