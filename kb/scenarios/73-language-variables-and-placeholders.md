# Scenario: Language variables (translations) from PHP/YAML + placeholders

## Goal

Maintain translation strings in a flexible way and support placeholder substitution in translations.

## Inputs to ask for

- How translations are managed (small fixed set vs external workflow)
- Whether translations should be stored as PHP arrays, YAML, or generated dynamically
- Whether translation strings need placeholders (filename, counts, links, etc.)

## Internal tools/resources to use

- Confirm language setup: `kirby://config/languages`
- Inspect language files in the repo (`site/languages/*.php`)

## Implementation steps

1. Store translations in the language files (`translations` array).
2. If you need more flexibility:
   - load translations from YAML (`Yaml::decode(F::read(...))`)
   - or call a function (e.g. backed by a structure field or external source)
3. For placeholder substitution, use `I18n::template()`.

## Examples (quicktips)

### Load translations from a YAML file

`site/languages/de.php` (excerpt)

```php
return [
  'code' => 'de',
  'translations' => Yaml::decode(F::read(kirby()->root('languages') . '/vars/de.yml')),
];
```

### Use placeholders in translation strings

```php
echo I18n::template('file.success', null, [
  'filename' => $file->filename(),
]);
```

## Verification

- Switch language and confirm translations load from the chosen source (PHP/YAML/function).
- Confirm placeholder replacements render correctly and remain escaped/safe where needed.

## Glossary quick refs

- kirby://glossary/language
- kirby://glossary/yaml
- kirby://glossary/languages
- kirby://glossary/i18n

## Links

- Quicktip: Language variables: https://getkirby.com/docs/quicktips/language-variables
- Quicktip: Variables in language strings: https://getkirby.com/docs/quicktips/using-variables-in-language-strings
- Guide: Custom language variables: https://getkirby.com/docs/guide/languages/custom-language-variables
- Reference: `I18n::template()`: https://getkirby.com/docs/reference/tools/i18n/template
