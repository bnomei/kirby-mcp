# Scenario: Use blueprints programmatically in the frontend

## Goal

Read blueprint metadata at runtime to:

- build dynamic UIs (e.g. frontend forms that mirror Panel fields)
- read options/labels for select fields
- access custom blueprint options

## Inputs to ask for

- Which model’s blueprint to read (page/file/user/site)
- Which blueprint parts are needed (fields, sections, options)
- Whether the blueprint contains custom options you rely on

## Internal tools/resources to use

- Read resolved blueprint via MCP:
  - `kirby://blueprint/{encodedId}` / `kirby_blueprint_read`
- Validate page content and types:
  - `kirby://page/content/{encodedIdOrUuid}` / `kirby_read_page_content`

## Implementation steps

1. Access blueprint via `$page->blueprint()`.
2. Read `name()`, `title()`, `fields()`, `field('...')`, `section('...')`, `options()`.
3. Use values carefully; always fall back if keys are missing.

## Examples (cookbook snippets)

```php
$blueprint = $page->blueprint();
$name = $blueprint->name();
$fields = $blueprint->fields();
$field = $blueprint->field('text');
```

## Verification

- Dump/inspect blueprint name/fields during development and confirm they match expected blueprint files.
- Add safe fallbacks when expected fields/options are missing.

## Glossary quick refs

- kirby://glossary/blueprint
- kirby://glossary/field
- kirby://glossary/option
- kirby://glossary/section

## Links

- Cookbook: Blueprints in frontend: https://getkirby.com/docs/cookbook/unclassified/blueprints-in-frontend
- Reference: `$page->blueprint()`: https://getkirby.com/docs/reference/objects/cms/page/blueprint
