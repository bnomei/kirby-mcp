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
1. Backup first (content + accounts + site config).
2. Implement a cleanup script that:
   - boots Kirby
   - impersonates (`$kirby->impersonate('kirby')`)
   - compares `$model->content()->fields()` vs `$model->blueprint()->fields()`
   - updates undefined fields to `null`
3. Run once, verify, then delete the script/route.

## Examples (cookbook approach; abridged)
```php
$kirby = new Kirby;
$kirby->impersonate('kirby');

$ignore = ['uuid', 'title', 'slug', 'template', 'sort', 'focus'];

foreach ($kirby->models() as $model) {
  $contentFields = array_keys($model->content()->fields());
  $blueprintFields = array_keys($model->blueprint()->fields());
  $fieldsToDelete = array_diff($contentFields, $blueprintFields, $ignore);

  if ($fieldsToDelete) {
    $data = array_map(fn () => null, array_flip($fieldsToDelete));
    $model->update($data);
  }
}
```

## Verification
- Check a few content files before/after.
- Confirm essential fields weren’t removed accidentally.

## Glossary quick refs

- kirby://glossary/content
- kirby://glossary/file
- kirby://glossary/field
- kirby://glossary/blueprint

## Links
- Cookbook: Content clean-up: https://getkirby.com/docs/cookbook/development-deployment/content-file-cleanup
- Reference: `$model->blueprint()`: https://getkirby.com/docs/reference/objects/cms/page/blueprint
