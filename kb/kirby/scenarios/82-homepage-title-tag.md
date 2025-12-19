# Scenario: Homepage `<title>` (avoid duplicate/awkward titles)

## Goal
Set the HTML `<title>` tag so the homepage title reads well (often the default “Home” title is not desired) while keeping other pages consistent.

## Inputs to ask for
- Desired title format (e.g. `Site — Page`, `Page | Site`)
- Separator and ordering rules for homepage vs other pages
- Where the `<title>` tag is rendered (usually a head snippet)

## Internal tools/resources to use
- Find the head snippet/template: `kirby_snippets_index`, `kirby_templates_index`
- Validate output: `kirby_render_page` and view page source

## Implementation steps
1. Update the `<title>` tag logic in the shared head snippet.
2. Use `$page->isHomePage()` to switch homepage behavior.
3. Keep output escaped (titles can contain user content).

## Examples (quicktip patterns)

### Page title first, site title second (except homepage)
```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<title>
  <?php if ($page->isHomePage()): ?>
    <?= $site->title()->escape() ?>
  <?php else: ?>
    <?= $page->title()->escape() ?> | <?= $site->title()->escape() ?>
  <?php endif ?>
</title>
```

## Verification
- Render the homepage and a normal page and confirm the `<title>` matches the desired format.
- Confirm `<title>` is not duplicated in nested snippets/layouts.

## Glossary quick refs

- kirby://glossary/snippet
- kirby://glossary/template

## Links
- Quicktip: Homepage title: https://getkirby.com/docs/quicktips/homepage-title
