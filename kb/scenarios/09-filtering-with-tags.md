# Scenario: Filter a listing by tag (`param('tag')`) + render a tag cloud

## Goal

Add tag-based filtering for a listing (typical: blog), controlled via URL params like `.../tag:design`.

## Inputs to ask for

- Which page/template lists items (e.g. `blog.php`)
- Which field stores tags (usually `tags`)
- Separator used in stored tags (commonly comma `,`)
- Whether tags should be free-form or restricted to allowed options

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Check controller/template presence: `kirby_controllers_index`, `kirby_templates_index`
- Render and inspect: `kirby_render_page`
- Panel field reference: `kirby://field/tags`

## Implementation steps

1. Add a `tags` field to the relevant page blueprint(s) so editors can set tags in the Panel.
2. Implement filtering logic in the listing controller:
   - fetch all tags for a tag cloud with `pluck(...)`
   - apply `filterBy('tags', $tag, ',')` only when a tag param exists
   - paginate filtered results
3. Render:
   - listing items
   - tag cloud links with `url(..., ['params' => ['tag' => $tag]])`

## Examples

### Blueprint: tags field

```yaml
fields:
  tags:
    label: Tags
    type: tags
```

### Tag cloud links (template/snippet)

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php foreach ($tags as $tag): ?>
  <a href="<?= url($page->url(), ['params' => ['tag' => $tag]]) ?>"><?= html($tag) ?></a>
<?php endforeach ?>
```

### Controller (blog)

`site/controllers/blog.php`

```php
<?php

return function ($page) {
    $articles = $page->children()->listed()->flip();

    $tags = $articles->pluck('tags', ',', true);

    if ($tag = param('tag')) {
        $articles = $articles->filterBy('tags', $tag, ',');
    }

    $articles   = $articles->paginate(10);
    $pagination = $articles->pagination();

    return compact('articles', 'tags', 'tag', 'pagination');
};
```

## Verification

- Open the listing normally and via a tag URL (e.g. `.../tag:design`) and compare results.
- Confirm pagination works with the filter applied.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/controller
- kirby://glossary/pagination
- kirby://glossary/template

## Links

- Cookbook: Filtering with tags: https://getkirby.com/docs/cookbook/collections/filtering-with-tags
- Quicktip: Tags: https://getkirby.com/docs/quicktips/tags
- Reference: Tags field: https://getkirby.com/docs/reference/panel/fields/tags
