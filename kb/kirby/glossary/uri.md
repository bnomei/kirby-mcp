# URI (aliases: `$page->uri()`, “translated id”)

## Meaning

A page’s “uri” is the same as its `id`, except that it will be **translated in multi-language setups**. On single-language sites, `uri()` and `id()` are typically identical.

This matters when you generate language-specific URLs or when you compare paths across languages.

## In prompts (what it usually implies)

- “Page URI” often means the language-aware path (and is sometimes confused with “URL”).
- “Why doesn’t `id()` change per language?” means: use `uri()` for translated paths.

## Variants / aliases

- `$page->uri()` (current language)
- `$page->uri('de')` (specific language)
- `$page->id()` (non-translated)

## Example

```php
<?= $page->id() ?>
<?= $page->uri() ?>
<?= $page->uri('de') ?>
```

## MCP: Inspect/verify

- Use `kirby_eval` to compare `id()` and `uri()` in your project:
  - example: `return [page('blog')?->id(), page('blog')?->uri('de')];`
- Confirm multi-language configuration:
  - read `kirby://config/languages`

## Related terms

- kirby://glossary/id
- kirby://glossary/slug
- kirby://glossary/uid
- kirby://glossary/language

## Links

- https://getkirby.com/docs/reference/objects/cms/page/uri

