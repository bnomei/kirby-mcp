# Scenario: Conditionally load frontend libraries per template/page

## Goal

Load third-party JS/CSS libraries only where needed to:

- keep pages fast
- avoid shipping unused assets site-wide

## Inputs to ask for

- Which templates/pages need the library
- Whether to load from local assets or CDN
- Whether library initialization needs page-specific data
- Any policy constraints on external CDNs (privacy/perf)

## Internal tools/resources to use

- Inventory templates/snippets: `kirby_templates_index`, `kirby_snippets_index`
- Validate output HTML: `kirby_render_page`

## Implementation steps

1. Add conditional asset tags in `header.php`/`footer.php` snippets:
   - check template name via `$page->intendedTemplate()->name()`
2. Use `css()`/`js()` helpers so paths are consistent with Kirby asset URLs.
3. Keep per-template CSS in dedicated files (optional).
4. Initialize JS in the footer (after the DOM exists) for the target template only.
5. Prefer local assets over CDNs unless there’s a strong reason to use a CDN.

## Examples (cookbook idea)

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php if ($page->intendedTemplate()->name() === 'album'): ?>
  <?= css('assets/js/glider/glider.css') ?>
<?php endif ?>
```

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php if ($page->intendedTemplate()->name() === 'album'): ?>
  <?= js('assets/js/glider/glider.js') ?>
<?php endif ?>
```

## Verification

- Confirm the library assets are only included on the intended templates.
- Confirm pages without the library do not include unused JS/CSS.
- Check that initialization only runs on pages that include the library.

## Glossary quick refs

- kirby://glossary/template
- kirby://glossary/asset
- kirby://glossary/snippet

## Links

- Cookbook: Frontend libraries: https://getkirby.com/docs/cookbook/frontend/frontend-libraries
- Guide: Snippets: https://getkirby.com/docs/guide/templates/snippets
