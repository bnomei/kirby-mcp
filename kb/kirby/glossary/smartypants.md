# SmartyPants (aliases: `smartypants()`, `->smartypants()`, `smartypants` option)

## Meaning

SmartyPants is a typographic “prettyfier”: it converts plain ASCII punctuation into typographically nicer output (smart quotes, dashes, ellipsis, etc.). In Kirby, SmartyPants can be enabled globally via the `smartypants` config option and is applied when you render text with `->kirbytext()` or `->smartypants()`.

## In prompts (what it usually implies)

- “Smart quotes/dashes” means enabling/configuring SmartyPants.
- “Only for a single string” often means calling the `smartypants()` helper directly.
- “Language-specific typography” means defining SmartyPants replacements per language file.

## Variants / aliases

- Config option: `smartypants` (boolean or array of replacements)
- Helper: `smartypants($text)`
- Field method: `$field->smartypants()`
- Core component override: a plugin can replace the SmartyPants parser component

## Example

```php
<?= smartypants($page->title()) ?>
```

## MCP: Inspect/verify

- Check whether SmartyPants is enabled and how it’s configured:
  - read `kirby://config/smartypants` (requires `kirby_runtime_install`)
- If output differs by language, inspect the language files (SmartyPants can be configured per language).
- Use `kirby_eval` to test the transformation quickly:
  - example: `return smartypants('\"Hello\" -- world...');`

## Related terms

- kirby://glossary/kirbytext
- kirby://glossary/markdown
- kirby://glossary/option
- kirby://glossary/language
- kirby://glossary/component

## Links

- https://getkirby.com/docs/glossary#smartypants
- https://getkirby.com/docs/reference/templates/helpers/smartypants
- https://getkirby.com/docs/reference/system/options/smartypants
- https://getkirby.com/docs/guide/content/text-formatting
- https://getkirby.com/docs/reference/plugins/components/smartypants

