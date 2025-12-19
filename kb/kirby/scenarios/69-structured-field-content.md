# Scenario: Structured field content (`yaml()` vs `toStructure()`)

## Goal
Render and manipulate data from:
- `structure` fields (recommended: `toStructure()`)
- fields stored as YAML in content files (`yaml()`)

## Inputs to ask for
- Field type (`structure`, textarea-as-yaml, etc.)
- Desired output (list, table, grouped)
- Whether items need validation/sorting/filtering

## Internal tools/resources to use
- Inspect blueprints: `kirby://blueprint/{encodedId}`
- Inspect real content values: `kirby://page/content/{encodedIdOrUuid}`
- Validate output: `kirby_render_page`

## Implementation steps
1. For `structure` fields:
   - use `$page->field()->toStructure()` and iterate items
2. For YAML stored in plain fields:
   - use `$page->field()->yaml()` (array) when you don’t need structure object helpers
3. Escape output (as always).

## Examples (quicktip snippets)

### YAML array
```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */

$addresses = $page->addresses()->yaml();
```

### Structure objects
```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php foreach ($page->addresses()->toStructure() as $address): ?>
  <p><?= $address->street()->escape() ?></p>
<?php endforeach ?>
```

## Verification
- Render the template and confirm each structure entry is output as expected.
- Confirm empty/invalid YAML doesn’t throw errors (add guards if needed).

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content
- kirby://glossary/yaml
- kirby://glossary/blueprint

## Links
- Quicktip: Structured field content: https://getkirby.com/docs/quicktips/structured-field-content
- Reference: Structure field: https://getkirby.com/docs/reference/panel/fields/structure
