# Scenario: Use blueprints programmatically in the frontend

## Goal

Read blueprint metadata at runtime to:

- build dynamic UIs (e.g. frontend forms that mirror Panel fields)
- read options/labels for select fields
- access custom blueprint options

## Inputs to ask for

- Which model’s blueprint to read (page/file/user/site)
- Which blueprint parts are needed (fields, sections, options)
- Whether the blueprint contains custom options you rely on (and if they are prefixed)

## Internal tools/resources to use

- Read resolved blueprint via MCP:
  - `kirby://blueprint/{encodedId}` / `kirby_blueprint_read`
- Validate page content and types:
  - `kirby://page/content/{encodedIdOrUuid}` / `kirby_read_page_content`

## Implementation steps

1. Access the blueprint via `$page->blueprint()` (also available on site, file, and user models).
2. Read `name()`, `title()`, `fields()`, `field('...')`, `section('...')`, `options()`, and `model()` as needed.
3. Treat `section('...')` as a `Section` object; guard for `null` when the key is missing.
4. For custom options, use `options()['yourKey'] ?? <fallback>` and avoid clashing with built-in blueprint keys (prefix custom options if possible).

## Examples (cookbook snippets)

```php
$blueprint = $page->blueprint();
$name = $blueprint->name();
$fields = $blueprint->fields();
$field = $blueprint->field('text');
$autoPublish = $blueprint->options()['autoPublish'] ?? false;
```

## Verification

- Dump/inspect blueprint name/fields during development and confirm they match expected blueprint files.
- Add safe fallbacks when expected fields/options are missing.
- If in doubt, compare runtime data to `kirby_blueprint_read` for the same id.

## Glossary quick refs

- kirby://glossary/blueprint
- kirby://glossary/field
- kirby://glossary/option
- kirby://glossary/section

## Links

- Cookbook: Blueprints in frontend: https://getkirby.com/docs/cookbook/unclassified/blueprints-in-frontend
- Reference: `$page->blueprint()`: https://getkirby.com/docs/reference/objects/cms/page/blueprint
- Reference: `Blueprint` object: https://getkirby.com/docs/reference/objects/cms/blueprint
