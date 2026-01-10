# Scenario: Filter pages to only those translated in current language

## Goal

In multi-language setups, prevent fallback-to-default-language pages from showing up by filtering for existing translations.

## Inputs to ask for

- Which collection needs filtering (menus, listings, search results)
- Which language is considered “current” (user-selected vs forced)
- Whether fallback behavior should ever be allowed

## Internal tools/resources to use

- Confirm language setup: `kirby://config/languages`
- Validate output: `kirby_render_page`

## Implementation steps

1. Ensure multi-language is enabled and a current language is set.
2. Filter collections with `translation(<langCode>)->exists()` to avoid fallback content.

## Examples (quicktip)

```php
$translatedPages = page('somepage')->children()->filter(
  fn ($child) => $child->translation(kirby()->language()->code())->exists()
);
```

## Verification

- Switch language and confirm un-translated pages disappear from the collection.

## Glossary quick refs

- kirby://glossary/language
- kirby://glossary/languages
- kirby://glossary/collection

## Links

- Quicktip: Filter by language: https://getkirby.com/docs/quicktips/filter-by-language
- Guide: Languages: https://getkirby.com/docs/guide/languages
