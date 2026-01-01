# KirbyText (aliases: `->kt()`, `kirbytext()`, “Markdown + KirbyTags”)

## Meaning

KirbyText is Kirby’s extended text format: it combines Markdown with KirbyTags (shortcodes) and is commonly used for rich text fields.

In templates, you typically render KirbyText from a field with the `kt()` field method.

## In prompts (what it usually implies)

- “Render text field with KirbyText” means: `$page->text()->kt()`.
- “KirbyTag not working” means: a tag isn’t registered/allowed, or the content isn’t being parsed as KirbyText.
- “Disable/adjust formatting” can involve configuration options and/or custom KirbyTags.

## Variants / aliases

- Field method: `kt()` / `kirbytext()` (depending on context)
- Helpers: `kirbytext()`, `kirbytags()`, `kirbytag()`
- Extensible via plugin extension: `kirbytags` (custom KirbyTags)

## Example

```php
<div class="text">
  <?= $page->text()->kt() ?>
</div>
```

## MCP: Inspect/verify

- Inspect the raw field content with `kirby_read_page_content` and confirm it contains KirbyText/KirbyTags.
- Render a real page with `kirby_render_page` to see the final HTML output.
- If KirbyTags are involved, use `kirby://extension/kirbytags` to review how tags are registered and check installed plugins with `kirby_plugins_index`.

## Related terms

- kirby://glossary/kirbytag
- kirby://glossary/field-method
- kirby://glossary/smartypants
- kirby://glossary/plugin

## Links

- https://getkirby.com/docs/guide/content/text-formatting#kirbytext
- https://getkirby.com/docs/reference/text/kirbytags
- https://getkirby.com/docs/reference/plugins/extensions/kirbytags
