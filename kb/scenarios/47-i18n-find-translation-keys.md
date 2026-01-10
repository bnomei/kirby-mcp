# Scenario: Find translation keys used in templates/controllers

## Goal

Extract and audit translation keys used with `t('key', 'fallback')` so you can:

- add missing keys to language files
- keep translations in sync as the code evolves

## Inputs to ask for

- Which folders to scan (`site/templates`, `site/snippets`, `site/controllers`, plugins)
- Which translation helpers are used (`t()`, `I18n::translate()`, `I18n::template()`, etc.)
- Desired output format (list of keys, PHP array skeleton, CSV, …)

## Internal tools/resources to use

- Use ripgrep for fast scanning (`rg`) in the repo.
- Confirm language roots: `kirby://roots`

## Implementation steps

1. Search for `t(` usage across templates/snippets/controllers (and `I18n::template()` if used).
2. Extract keys and compare against language files in `site/languages/*.php`.
3. Add missing keys with sensible defaults.

## Examples (adapted for `rg`)

```sh
rg -n \"\\bt\\(\" site/templates site/snippets site/controllers site/plugins
rg -n \"I18n::template\\(\" site/templates site/snippets site/controllers site/plugins
```

## Verification

- Switch language and ensure the UI no longer falls back unexpectedly.

## Glossary quick refs

- kirby://glossary/i18n
- kirby://glossary/language
- kirby://glossary/controller
- kirby://glossary/template

## Links

- Cookbook: Find translations: https://getkirby.com/docs/cookbook/i18n/find-translations
- Quicktip: Using variables in language strings: https://getkirby.com/docs/quicktips/using-variables-in-language-strings
