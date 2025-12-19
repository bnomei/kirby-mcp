# Scenario: Replace placeholders in text (Str::template + options)

## Goal
Allow editors to write placeholders in text fields (e.g. `{{ email }}`) and replace them at render time with configured values.

## Inputs to ask for
- Placeholder syntax and source (config option vs site fields)
- Which fields should support placeholders (KirbyText? plain text?)
- Whether placeholders should be global (site-wide) or per page

## Internal tools/resources to use
- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Read config options: `kirby://config/{option}` (when runtime is installed)
- Inspect site/page fields: `kirby://page/content/{encodedIdOrUuid}` (or `kirby_read_page_content`)
- Validate rendering: `kirby_render_page`

## Implementation steps
1. Pick where replacement values live:
   - config option like `option('placeholders')`
   - site content (`$site->placeholders()`), etc.
2. Replace placeholders at output time with `Str::template(...)`.
3. Optional: wrap replacement into a custom field method or hook so templates stay clean.

## Examples (cookbook patterns)

### Replace placeholders using config options
`site/config/config.php`
```php
<?php

return [
  'placeholders' => [
    'email' => 'hello@example.com',
    'phone' => '+1 555 123',
  ],
];
```

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?= Str::template($page->text()->kt(), option('placeholders')) ?>
```

### Replace placeholders using site field options
```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?= Str::template($page->text()->kt(), $site->placeholders()->toOptions()) ?>
```

## Verification
- Put a placeholder like `{{ email }}` into the target field.
- Render the page and confirm it is replaced correctly.

## Glossary quick refs

- kirby://glossary/template
- kirby://glossary/option
- kirby://glossary/field
- kirby://glossary/roots

## Links
- Cookbook: Use placeholders: https://getkirby.com/docs/cookbook/content-structure/use-placeholders
- Reference: `Str::template()`: https://getkirby.com/docs/reference/toolkit/str/template
