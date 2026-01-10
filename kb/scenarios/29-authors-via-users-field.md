# Scenario: Add authors via users + `users` field

## Goal

Model “authors” using Kirby users (with a dedicated role/blueprint) and assign authors to content pages (e.g. articles) via the `users` field.

## Inputs to ask for

- Single or multiple authors per page
- Which author profile fields are needed (bio, avatar, website, socials)
- Where author info should appear (article template, authors index page, both)
- Whether the project uses UUIDs for users (default: yes)
- Whether the author picker should be restricted to a role (via `query`)

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Inspect existing blueprints/templates:
  - `kirby_blueprints_index` + `kirby://blueprint/{encodedId}`
  - `kirby_templates_index`
- Validate output: `kirby_render_page`

## Implementation steps

1. Create a user blueprint for authors:
   - `site/blueprints/users/author.yml`
2. Add a `users` field to the target page blueprint (e.g. `pages/article.yml`).
   - Optionally restrict to `author` role with `query`
   - If you must store IDs instead of UUIDs, set `store: id`
3. In the template:
   - resolve author(s) with `toUser()` / `toUsers()`
   - render avatar and profile fields
4. Optional: add an `authors.php` page/template to list authors and their articles.

## Examples (cookbook pattern)

### User blueprint (role profile fields)

`site/blueprints/users/author.yml`

```yaml
title: Author
fields:
  bio:
    label: Bio
    type: textarea
  website:
    label: Website
    type: url
```

### Page blueprint: select a single author

`site/blueprints/pages/article.yml` (excerpt)

```yaml
author:
  type: users
  multiple: false
  query: kirby.users.filterBy('role', 'author')
```

### Template: render author block

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php if ($author = $page->author()->toUser()): ?>
  <aside class="author">
    <h2><?= $author->name()->escape() ?></h2>
    <?php if ($avatar = $author->avatar()): ?>
      <img src="<?= $avatar->url() ?>" alt="">
    <?php endif ?>
    <?= $author->bio()->kt() ?>
  </aside>
<?php endif ?>
```

## Verification

- Select an author in the Panel and confirm the content file stores a user UUID (or ID if `store: id`).
- Render an article page and confirm author info appears without errors.
- Optionally evaluate the query with `kirby_query_dot` before editing the blueprint (pass `model=blog/post` or a `page://...` UUID when the query relies on `page.*`).

## Glossary quick refs

- kirby://glossary/users
- kirby://glossary/field
- kirby://glossary/blueprint
- kirby://glossary/template

## Links

- Cookbook: Authors: https://getkirby.com/docs/cookbook/content-structure/authors
- Reference: Users field: https://getkirby.com/docs/reference/panel/fields/users
- Guide: Users: https://getkirby.com/docs/guide/users
- Guide: UUIDs: https://getkirby.com/docs/guide/uuids
