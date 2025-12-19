# Scenario: Custom post types via template variants (e.g. `article.video`)

## Goal
Support multiple “post types” with different templates while keeping a unified section like a blog.

Pattern:
- each post folder contains a type-specific content file like `article.video.txt`
- Kirby uses the content filename to pick the matching template like `article.video.php`

## Inputs to ask for
- Which post types you need (`text`, `video`, `image`, `quote`, `link`, …)
- Which fields differ per type
- How listing previews should differ per type (blog overview)
- Whether Panel blueprints should differ per type as well

## Internal tools/resources to use
- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Inventory templates/snippets/blueprints: `kirby_templates_index`, `kirby_snippets_index`, `kirby_blueprints_index`
- Render and inspect: `kirby_render_page`

## Implementation steps
1. Create type-specific content files for posts:
   - `content/blog/my-post/article.video.txt`
2. Create matching templates:
   - `site/templates/article.video.php` (and others)
3. Keep templates DRY with snippets:
   - per-type preview snippets for the blog listing
4. Optional: create per-type blueprints so the Panel matches each type’s fields.

## Examples

### Blog listing: switch by template name
```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php foreach ($articles as $article): ?>
  <?php $type = $article->template()->name(); ?>
  <?php if ($type === 'article.video'): ?>
    <!-- preview HTML for video post -->
  <?php elseif ($type === 'article.text'): ?>
    <!-- preview HTML for text post -->
  <?php endif ?>
<?php endforeach ?>
```

### Blog listing: delegate previews to snippets
```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php foreach ($articles as $article): ?>
  <article>
    <?php snippet('blog/article.preview.' . $article->template()->name(), ['article' => $article]) ?>
  </article>
<?php endforeach ?>
```

## Verification
- Confirm each post folder’s content filename matches the desired template variant.
- Render a few posts and confirm the correct template is used for each type.

## Glossary quick refs

- kirby://glossary/template
- kirby://glossary/snippet
- kirby://glossary/blueprint
- kirby://glossary/roots

## Links
- Cookbook: Custom post types: https://getkirby.com/docs/cookbook/content-structure/custom-post-types
- Guide: Templates basics (file name mapping): https://getkirby.com/docs/guide/templates/basics
