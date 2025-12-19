# Scenario: Translate exception messages (single-language sites)

## Goal
When throwing/catching Kirby exceptions during programmatic operations (create page/user/file), ensure displayed messages are translated instead of always English.

## Inputs to ask for
- Target locale/language code (e.g. `de`)
- Whether this is a single-language or multi-language site
- Where exception messages are displayed (frontend forms, logs, etc.)

## Internal tools/resources to use
- Confirm locale config: `kirby://config/locale`
- Inspect language files: `site/languages/*.php`

## Implementation steps
1. In a catch block, render the translated exception message via:
   - `I18n::template($e->getKey(), null, $e->getData(), '<lang>')`
2. Or set `Kirby\Toolkit\I18n::$locale` before the action to affect `$e->getMessage()`.
3. Prefer using `option('locale')` so the code stays configurable.

## Examples (quicktip; abridged)
```php
Kirby\Toolkit\I18n::$locale = option('locale');

$kirby->impersonate('kirby');
try {
  // do write operation
} catch (Exception $e) {
  echo $e->getMessage();
}
```

## Verification
- Trigger a known exception (e.g. create a page with an existing slug) and confirm the message is translated.
- Confirm fallback behavior when no translation exists.

## Glossary quick refs

- kirby://glossary/language
- kirby://glossary/i18n
- kirby://glossary/template
- kirby://glossary/option

## Links
- Quicktip: Translate exception messages: https://getkirby.com/docs/quicktips/translate-exception-messages
- Reference: `I18n::template()`: https://getkirby.com/docs/reference/tools/i18n/template
