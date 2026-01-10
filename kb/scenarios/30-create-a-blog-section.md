# Scenario: Create a blog section (blog index + article pages)

## Goal

Create a standard “Blog” section:

- a parent page `blog` that lists articles
- child pages (articles) with an article template

## Inputs to ask for

- Blog page id/slug (usually `blog`)
- Article fields (text, date, cover image, tags, author, …)
- Listing requirements (sort order, pagination, excerpts, featured items)
- Whether articles should have multiple template variants (see `22-custom-post-types.md`)

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Inventory templates/snippets/controllers/blueprints:
  - `kirby_templates_index`, `kirby_snippets_index`, `kirby_controllers_index`
  - `kirby_blueprints_index`
- Validate rendering: `kirby_render_page`

## Implementation steps

1. Create page types:
   - `blog` (parent) and `article` (child)
2. Add templates:
   - `site/templates/blog.php`
   - `site/templates/article.php`
3. Add blueprints so editors can manage content in the Panel.
4. Keep listing logic in a controller if it grows (sorting/filtering/pagination).
   - If you rely on dates instead of numbered folders, sort by a date field.
5. Extend later with:
   - pagination (`07-pagination.md`)
   - tags (`09-filtering-with-tags.md`)
   - authors (`29-authors-via-users-field.md`)

## Examples (from the cookbook recipe)

### Blog template: list articles

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<h1><?= $page->title()->html() ?></h1>

<?php foreach ($page->children()->listed()->flip() as $article): ?>
  <article>
    <h2><a href="<?= $article->url() ?>"><?= $article->title()->html() ?></a></h2>
    <p><?= $article->text()->excerpt(300) ?></p>
  </article>
<?php endforeach ?>
```

If you don't use numbered folders, replace `flip()` with `sortBy('date', 'desc')`.

### Article template: simple “back” link

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<a href="<?= $page->parent()->url() ?>">Back…</a>
```

## Verification

- Create a blog page and a few listed article children.
- Render `/blog` and an article page and confirm both templates load correctly.

## Glossary quick refs

- kirby://glossary/section
- kirby://glossary/template
- kirby://glossary/controller
- kirby://glossary/pagination

## Links

- Cookbook: Create a blog: https://getkirby.com/docs/cookbook/content-structure/create-a-blog
- Guide: Templates basics: https://getkirby.com/docs/guide/templates/basics
- Guide: Controllers: https://getkirby.com/docs/guide/templates/controllers
