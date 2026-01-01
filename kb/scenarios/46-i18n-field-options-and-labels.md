# Scenario: Translate select options (and render labels, not raw values)

## Goal

For option fields (select/radio/multiselect/tags), store stable keys in content files but display translated labels in:

- the Panel
- frontend templates

## Inputs to ask for

- Single-language vs multi-language setup
- Which fields need translated labels (`category`, `type`, etc.)
- Where translations should live (language `translations`, config map, blueprint)

## Internal tools/resources to use

- Inspect language setup: `kirby://config/languages`
- Inspect blueprints and field options:
  - `kirby://blueprint/{encodedId}` / `kirby_blueprint_read`
- Validate output: `kirby_render_page`

## Implementation steps

1. In the blueprint, use stable keys (e.g. `design`, `web`) for option values.
2. Choose one of these strategies to render the label:
   - config option map (`option('category-map')`)
   - language file `translations` + `t()`
   - read labels directly from the blueprint via `$page->blueprint()->field(...)`
3. Always provide a fallback when a value is missing.

## Examples (cookbook patterns)

### Config map

```php
// site/config/config.php
return [
  'category-map' => [
    'design' => 'Design',
    'web'    => 'Web design',
  ]
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

<?= option('category-map')[$page->category()->value()] ?? $page->category()->escape() ?>
```

### Language `translations` + `t()`

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?= t($page->category()->value(), ucfirst($page->category()->html())) ?>
```

### Read option labels from blueprint

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */

$field = $page->blueprint()->field('category');
$value = $page->category()->value();
echo $field['options'][$value] ?? $value;
```

## Verification

- Switch language in the Panel and confirm option labels translate.
- Confirm templates render labels instead of raw stored keys.

## Glossary quick refs

- kirby://glossary/i18n
- kirby://glossary/field
- kirby://glossary/option
- kirby://glossary/language

## Links

- Cookbook: Fetching field options: https://getkirby.com/docs/cookbook/i18n/fetching-field-options
- Guide: Languages: https://getkirby.com/docs/guide/languages
- Reference: `t()` helper: https://getkirby.com/docs/reference/templates/helpers/t
