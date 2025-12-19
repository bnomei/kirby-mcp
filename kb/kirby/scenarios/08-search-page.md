# Scenario: Create a search page (controller + template)

## Goal

Add a dedicated search page with a query parameter (`?q=...`), display results, and paginate them.

## Inputs to ask for

- Where search should run:
  - entire site (`$site->search`)
  - a section only (`page('blog')->search`)
- Which fields to search (e.g. `title|text`)
- Results per page and desired UI

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Check existing pages/templates/controllers: `kirby_templates_index`, `kirby_controllers_index`
- Render and inspect: `kirby_render_page`

## Implementation steps

1. Create a `search` content page (Panel or content folder).
2. Add `site/templates/search.php` with the search form and results list.
3. Add `site/controllers/search.php` to compute `$query`, `$results`, `$pagination`.

## Examples

### Controller

`site/controllers/search.php`

```php
<?php

return function ($site) {
    $query = get('q');

    $results = $site->search($query, 'title|text')->paginate(20);

    return [
        'query'      => $query,
        'results'    => $results,
        'pagination' => $results->pagination(),
    ];
};
```

### Template (minimal)

`site/templates/search.php`

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php snippet('header') ?>

<form>
  <input type="search" name="q" value="<?= html($query) ?>" aria-label="Search">
  <input type="submit" value="Search">
</form>

<?php if ($results->isNotEmpty()): ?>
  <ul>
    <?php foreach ($results as $result): ?>
      <li><a href="<?= $result->url() ?>"><?= $result->title()->html() ?></a></li>
    <?php endforeach ?>
  </ul>
<?php endif ?>

<?php snippet('footer') ?>
```

## Verification

- Render with a query string (e.g. `...?q=test`) and confirm results appear.
- Confirm pagination works for longer result sets.

## Glossary quick refs

- kirby://glossary/page
- kirby://glossary/controller
- kirby://glossary/template
- kirby://glossary/pagination

## Links

- Cookbook: Search: https://getkirby.com/docs/cookbook/collections/search
- Reference: `$site->search()`: https://getkirby.com/docs/reference/objects/cms/site/search
