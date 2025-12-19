# Scenario: Build a one-pager site from section subpages

## Goal

Render a “one page” website where:

- `home` renders multiple section subpages in one page
- each section is rendered via a snippet
- visiting a section URL redirects back to `/#section-id`

## Inputs to ask for

- Which sections exist (uids) and their order
- Which sections have nested children (e.g. projects inside a section)
- Navigation behavior (scroll anchors, active section, deep links)

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Inventory templates/snippets: `kirby_templates_index`, `kirby_snippets_index`
- Validate rendering: `kirby_render_page`

## Implementation steps

1. Create `site/templates/home.php` that loops through section subpages.
2. Render each section via `snippet($section->uid(), ['data' => $section])`.
3. Add `site/snippets/<section>.php` per section.
4. Add a redirecting `default.php` template (or a route) so section URLs don’t render standalone.

## Examples (cookbook pattern)

### Home template: loop sections and include snippets

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php foreach ($page->children()->listed() as $section): ?>
  <?php snippet($section->uid(), ['data' => $section]) ?>
<?php endforeach ?>
```

### Default template: redirect subpage URLs

`site/templates/default.php`

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */

go();
```

## Verification

- Render the homepage and confirm all sections appear.
- Visit a section URL and confirm you end up back on the one-pager.

## Glossary quick refs

- kirby://glossary/site
- kirby://glossary/section
- kirby://glossary/snippet
- kirby://glossary/template

## Links

- Cookbook: One pager: https://getkirby.com/docs/cookbook/content-structure/one-pager
- Reference: `go()` helper: https://getkirby.com/docs/reference/templates/helpers/go
- Guide: Snippets: https://getkirby.com/docs/guide/templates/snippets
