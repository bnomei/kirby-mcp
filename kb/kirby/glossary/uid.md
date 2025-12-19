# UID (aliases: `$page->uid()`, “stable slug”)

## Meaning

A page’s “uid” is a stable identifier for the page’s slug across languages. In Kirby’s docs, the uid is “basically the same as the slug, but stays the same on multi-language sites. Whereas the slug can be translated.”

In practice: use `uid()` when you need a stable identifier and `slug()` when you need the language-specific URL segment.

## In prompts (what it usually implies)

- “The folder name / uid” usually means the stable (non-translated) slug.
- “Translate the slug” usually means changing the language-specific `slug()`, not the uid.

## Variants / aliases

- `$page->uid()` (stable slug)
- `$page->slug()` / `$page->slug('de')` (language-specific slug)
- `$page->id()` (full path based on non-translated segments)

## Example

```php
<?= $page->uid() ?>
```

## MCP: Inspect/verify

- Use `kirby_eval` to check `uid()` vs `slug()`:
  - example: `return [page('blog')?->uid(), page('blog')?->slug(), page('blog')?->slug('de')];`
- If multi-language is involved, confirm config first:
  - read `kirby://config/languages`

## Related terms

- kirby://glossary/slug
- kirby://glossary/id
- kirby://glossary/uri
- kirby://glossary/language

## Links

- https://getkirby.com/docs/reference/objects/cms/page/uid
- https://getkirby.com/docs/reference/objects/cms/page/slug

