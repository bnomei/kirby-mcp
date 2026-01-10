# Scenario: Import/export translations (script workflow)

## Goal

Automate translation workflows by exporting/importing **content translations** (page/file content per language) so translators can work outside the repo.

## Inputs to ask for

- Which format translators need (JSON, CSV, PHP array)
- Which languages are involved
- Which models to include (pages, files)
- Whether scripts can be committed to the repo (usually yes)

## Internal tools/resources to use

- Confirm project roots: `kirby://roots`
- Confirm languages config: `kirby://config/languages`

## Implementation steps

1. Add scripts (CLI PHP) to export/import translations.
2. Export translated content from pages/files (`$model->content($language)->toArray()`) into a transport format (often JSON).
3. Import updated translations back into content files with `$model->update($content, $language)` (impersonate `kirby`).
4. Backup before import; the import overwrites language content.
5. Run scripts with care and commit changes.

## Examples

- Export script: `scripts/translation-export`
- Import script: `scripts/translation-import`

## Verification

- Run export and confirm output files contain the expected keys/values.
- Run import and confirm language files are updated and still valid PHP.

## Glossary quick refs

- kirby://glossary/i18n
- kirby://glossary/language
- kirby://glossary/languages
- kirby://glossary/roots

## Links

- Cookbook: Import/export translations: https://getkirby.com/docs/cookbook/i18n/import-export
- Guide: Languages: https://getkirby.com/docs/guide/languages
