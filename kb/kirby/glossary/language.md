# Language (aliases: `$language`, `Kirby\Cms\Language`, `$kirby->language()`)

## Meaning

In multi-language Kirby installations, a “language” is a configured locale (code, name, direction, locale settings, URL rules, etc.). The current language influences content translation files, localized URLs/slugs, and translations for `t()` language variables.

You typically get a language object from `$kirby` (current, default, or by code).

## In prompts (what it usually implies)

- “Current language” means: `$kirby->language()` (or `$kirby->currentLanguage()` depending on context).
- “Switch language” usually means: use Kirby’s language routing + language switcher URLs.
- “Different slugs per language” means: language-specific slug rules and translated URLs/content.

## Variants / aliases

- `$kirby->language()` (current language)
- `$kirby->defaultLanguage()` (default language)
- `$kirby->language('de')` (get by code)
- Language definition files: `site/languages/<code>.php`

## Example

```php
<?php

$language = $kirby->language();
echo $language->code();
```

## MCP: Inspect/verify

- Check whether languages are enabled and how they’re configured:
  - read `kirby://config/languages` (requires `kirby_runtime_install`)
- Use `kirby_roots` to locate the `languages` root (don’t assume `site/languages`).
- Use `kirby_eval` to inspect runtime language state:
  - example: `return kirby()->language()?->toArray();`

## Related terms

- kirby://glossary/languages
- kirby://glossary/i18n
- kirby://glossary/option

## Links

- https://getkirby.com/docs/reference/objects/cms/language
- https://getkirby.com/docs/guide/languages
