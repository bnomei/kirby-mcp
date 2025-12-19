# Markdown (aliases: `$field->markdown()`, `$field->kirbytext()`)

## Meaning

Markdown is a lightweight text format that Kirby can convert to HTML. In Kirby, Markdown is commonly used either directly (`markdown()`) or as part of KirbyText (Markdown + KirbyTags).

## In prompts (what it usually implies)

- “Render Markdown” means: use `$field->markdown()` (or `markdown()` helper for strings).
- “Render rich text with embeds/tags” means: use KirbyText (`$field->kirbytext()` / `$field->kt()`).
- “Enable markdown extra” means: set `markdown.extra` option.

## Variants / aliases

- Field methods: `markdown()`, `kirbytext()` (alias: `kt()`)
- Config: `markdown.extra` and other markdown options

## Example

```php
<?= $page->text()->markdown() ?>
```

## MCP: Inspect/verify

- Inspect the raw field content via `kirby_read_page_content` (helps differentiate Markdown vs KirbyText expectations).
- Verify final HTML output via `kirby_render_page`.
- Check markdown-related config (runtime install required):
  - `kirby://config/markdown`

## Related terms

- kirby://glossary/kirbytext
- kirby://glossary/kirbytag
- kirby://glossary/field-method
- kirby://glossary/smartypants

## Links

- https://getkirby.com/docs/guide/content/text-formatting#markdown
- https://getkirby.com/docs/reference/text/markdown
- https://getkirby.com/docs/reference/system/options/markdown
