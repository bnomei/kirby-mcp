# Scenario: Clean up unused fields in content files (safe migration script)

## Goal

Remove stale keys from content files after:

- blueprint fields were removed/renamed
- fields were added manually during development

This is a destructive operation: treat it like a migration.

## Inputs to ask for

- Whether the project is multi-language
- Which models to include (pages, files, users)
- Which fields must be preserved even if not in blueprints (`uuid`, `title`, …)
- Where and how to run the migration (one-off script vs route)

## Internal tools/resources to use

- Confirm environment: `kirby://info`
- Confirm roots (especially `content` + `site`): `kirby://roots`
- Inspect blueprint fields: `kirby://blueprint/{encodedId}`
- Prefer a dry-run mode if you implement one (log instead of update)

## Implementation steps

1. Backup first (content + accounts + site config). Resolve all unsaved Panel changes before running; this script cleans only saved content. Do not run if fields absent from blueprints are intentional unless they are allowlisted.
2. Implement a cleanup script that:
   - boots Kirby
   - impersonates (`$kirby->impersonate('kirby')`)
   - reads `$model->version('latest')->read($language)` without language fallback
   - skips languages with no saved content file
   - compares stored field keys against blueprint field keys and the preserve list
   - replaces saved content with the retained fields using `$version->replace()`
   - iterates languages when `kirby()->multilang()` is true (otherwise uses `default`)
3. Run once, verify, then delete the script/route.

## Examples (cookbook approach; abridged)

```php
$kirby = new Kirby;
$kirby->impersonate('kirby');

$ignore = ['uuid', 'title', 'slug', 'template', 'sort', 'focus'];
$languages = $kirby->multilang() ? $kirby->languages()->codes() : ['default'];

foreach ($kirby->models() as $model) {
  $version = $model->version('latest');
  $blueprintFields = array_keys($model->blueprint()->fields());

  foreach ($languages as $language) {
    $content = $version->read($language);
    if ($content === null) {
      continue;
    }

    $fieldsToDelete = array_diff(array_keys($content), $blueprintFields, $ignore);
    if ($fieldsToDelete !== []) {
      $version->replace(array_diff_key($content, array_flip($fieldsToDelete)), $language);
    }
  }
}
```

## Verification

- Check a few content files before/after.
- Confirm essential fields weren’t removed accidentally.
- Verify that absent translations remain absent and that no default-language fallback was copied into another language.

## Glossary quick refs

- kirby://glossary/content
- kirby://glossary/file
- kirby://glossary/field
- kirby://glossary/blueprint

## Links

- Cookbook: Content clean-up: https://getkirby.com/docs/cookbook/development-deployment/content-file-cleanup
- Reference: `$model->blueprint()`: https://getkirby.com/docs/reference/objects/cms/page/blueprint
