# Slug (aliases: `$page->slug()`, “URL segment”, “translated slug”)

## Meaning

A page’s “slug” is the URL-friendly name used in its URL (the last segment). On multi-language sites, a slug can be **language-specific** (translated), which means it can differ per locale.

If you need a stable, non-translated identifier across languages, use the page’s `uid()`.

## In prompts (what it usually implies)

- “Change the slug” means: update the page’s slug (Panel or PHP API) and ensure links/routes still match.
- “Translated slugs” means: different slug per language (while the uid stays stable).
- “Slug rules” means: custom slug replacements (often per language).

## Variants / aliases

- `$page->slug()` (current language)
- `$page->slug('de')` (specific language)
- Slug/URL rules can be configured via:
  - `slugs` config option
  - `slugs` rules in language definition files

## Example

```php
<?= $page->slug() ?>
<?= $page->slug('de') ?>
```

## MCP: Inspect/verify

- Check multi-language setup first (slug translation only matters then):
  - read `kirby://config/languages` (requires `kirby_runtime_install`)
- Use `kirby_eval` to compare `slug()` vs `uid()` across languages:
  - example: `return [page('blog')?->uid(), page('blog')?->slug('de')];`
- If slug rules are suspected, inspect the language files and `slugs` option:
  - read `kirby://config/slugs`

## Related terms

- kirby://glossary/uid
- kirby://glossary/uri
- kirby://glossary/id
- kirby://glossary/language
- kirby://glossary/option

## Links

- https://getkirby.com/docs/reference/objects/cms/page/slug
- https://getkirby.com/docs/reference/system/options/slugs
- https://getkirby.com/docs/guide/languages

