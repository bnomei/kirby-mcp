# Scenario: Add “related content” via a `pages` field (`toPages()`)

## Goal

Let editors select related pages in the Panel and render them in templates.

## Inputs to ask for

- Where related links should appear (template name)
- Relationship direction:
  - manual selection (this scenario)
  - automatic (e.g. by tags/category) (different scenario)
- Which pages should be selectable (siblings? whole site? only a section?)

## Internal tools/resources to use

- Blueprint inspection: `kirby_blueprint_read` / `kirby://blueprint/{encodedId}`
- Panel field reference: `kirby://field/pages`
- Render and inspect: `kirby_render_page`

## Implementation steps

1. Add a `pages` field to the blueprint (limit candidates via `query:`).
2. In the template, convert the stored YAML list to a pages collection via `->toPages()`.
3. Render the related list only when non-empty.

## Examples

### Blueprint field

```yaml
related:
  label: Related articles
  type: pages
  query: page.siblings(false)
```

### Template rendering

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */

$related = $page->related()->toPages();
if ($related->isNotEmpty()):
?>
  <h2>Related</h2>
  <ul>
    <?php foreach ($related as $article): ?>
      <li><a href="<?= $article->url() ?>"><?= $article->title()->html() ?></a></li>
    <?php endforeach ?>
  </ul>
<?php endif ?>
```

## Verification

- Select related pages in the Panel and confirm the frontend renders them.

## Glossary quick refs

- kirby://glossary/pages
- kirby://glossary/field
- kirby://glossary/blueprint
- kirby://glossary/template

## Links

- Cookbook: Related articles: https://getkirby.com/docs/cookbook/collections/related-articles
- Reference: Pages field: https://getkirby.com/docs/reference/panel/fields/pages
- Reference: `toPages()`: https://getkirby.com/docs/reference/templates/field-methods/to-pages
