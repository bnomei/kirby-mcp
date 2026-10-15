# Scenario: Use blueprints programmatically in the frontend

## Goal

Read blueprint metadata at runtime to:

- build dynamic UIs (e.g. frontend forms that mirror Panel fields)
- read options/labels for select fields
- access custom blueprint options

## Inputs to ask for

- Which model’s blueprint to read (page/file/user/site)
- Which blueprint parts are needed (fields, tabs, options)
- Whether the blueprint contains custom options you rely on (and if they are prefixed)

## Internal tools/resources to use

- Read raw blueprint configuration via MCP (not the normalized model layout):
  - `kirby://blueprint/{encodedId}` / `kirby_blueprint_read`
- Validate page content and types:
  - `kirby://page/content/{encodedIdOrUuid}` / `kirby_read_page_content`

## Implementation steps

1. Access the blueprint via `$page->blueprint()` (also available on site, file, and user models).
2. Read `name()`, `title()`, `fields()`, `field('...')`, `options()`, and `model()` as needed. Kirby 6 classes live under `Kirby\Blueprint`; `tabs()` and `tab()` return objects rather than arrays.
3. Kirby 5 legacy `section()`/`sections()` are removed: use `field()`/`fields()`. Normalized fields include display-only `pagelist`, `filelist`, `info`, `stats` and other non-value types. Resolve the field class and check `ValueField` ancestry before building content inputs; do not assume every blueprint field stores content. MCP schemas expose `hasValue` as true/false/null (unresolved).
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
- Compare runtime data to `kirby_blueprint_read` for the same id, allowing for raw configuration versus normalized fields and root-level field references.

## Glossary quick refs

- kirby://glossary/blueprint
- kirby://glossary/field
- kirby://glossary/option
- kirby://glossary/section

## Links

- Cookbook: Blueprints in frontend: https://getkirby.com/docs/cookbook/unclassified/blueprints-in-frontend
- Reference: `$page->blueprint()`: https://getkirby.com/docs/reference/objects/cms/page/blueprint
- Kirby 6 blueprint API migration: https://getkirby.com/releases/6/migration/site-developers#custom-code-using-core-classes__blueprints-in-php
