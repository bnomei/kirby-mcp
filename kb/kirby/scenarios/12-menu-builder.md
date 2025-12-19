# Scenario: Build custom menus managed in the Panel (structure + pages/link fields)

## Goal

Let editors manage menus in the Panel instead of hard-coding navigation rules.

Common variants:

- multiple independent footer menus (variable count)
- mixed menus (pages + external URLs)
- two-level menus with optional submenus

## Inputs to ask for

- Where the menu config should live:
  - site blueprint fields (global menus)
  - a dedicated “navigation” page
- Which variants you need (multiple menus, mixed, nested)
- Link requirements (title override, target, rel, etc.)

## Internal tools/resources to use

- Blueprint inspection: `kirby_blueprint_read` / `kirby://blueprint/{encodedId}`
- Panel field references:
  - `kirby://field/structure`
  - `kirby://field/pages`
  - `kirby://field/link`
- Render and inspect: `kirby_render_page`

## Implementation steps

1. Add a structure field to store menu definitions.
2. Use `toStructure()` (for structure) + `toPages()`/`toPage()` (for selected pages).
3. Add guard clauses so empty menus don’t render empty `<nav>` elements.

## Examples

### Multiple independent menus (structure + pages)

Blueprint:

```yaml
menus:
  type: structure
  fields:
    menuHeadline:
      type: text
      label: Menu headline
    menuItems:
      type: pages
      label: Menu item
```

Template:

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php $menus = $site->menus()->toStructure(); ?>
<?php foreach ($menus as $menu): ?>
  <?php $items = $menu->menuItems()->toPages(); ?>
  <?php if ($items->isNotEmpty()): ?>
    <nav>
      <h4><?= $menu->menuHeadline()->html() ?></h4>
      <ul>
        <?php foreach ($items as $item): ?>
          <li><a<?= $item->isOpen() ? ' aria-current="page"' : '' ?> href="<?= $item->url() ?>"><?= $item->title()->html() ?></a></li>
        <?php endforeach ?>
      </ul>
    </nav>
  <?php endif ?>
<?php endforeach ?>
```

### Mixed menu (pages + url) using the link field

Blueprint:

```yaml
mixedMenu:
  type: structure
  fields:
    linkTitle:
      type: text
      label: Menu item title
    link:
      type: link
      label: Link
      options:
        - page
        - url
```

Template:

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php foreach ($site->mixedMenu()->toStructure() as $item): ?>
  <a href="<?= $item->link()->toUrl() ?>">
    <?= $item->linkTitle()->or($item->link()->html()) ?>
  </a>
<?php endforeach ?>
```

## Verification

- Create/edit menu entries in the Panel and confirm frontend output updates correctly.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/blueprint
- kirby://glossary/panel
- kirby://glossary/template

## Links

- Cookbook: Menu builder: https://getkirby.com/docs/cookbook/navigation/menu-builder
- Guide: Reusing blueprint field groups: https://getkirby.com/docs/guide/blueprints/extending-blueprints
