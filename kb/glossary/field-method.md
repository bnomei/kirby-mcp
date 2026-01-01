# Field method (aliases: `$field->kt()`, `$field->escape()`, “field methods”)

## Meaning

Field methods are methods you call on a `Kirby\Content\Field` object to **transform, format, validate, or convert** its value.

In everyday Kirby template code you’ll see chains like `$page->title()->escape()` or `$page->text()->kt()` (KirbyText rendering).

## In prompts (what it usually implies)

- “Use `kt()`/`kirbytext()`” means: render field text as KirbyText (Markdown + KirbyTags).
- “Escape output” means: use `escape()` (or a more specific method depending on context).
- “Convert to array/date/bool” means: use conversion field methods (see reference list).
- “Custom field method” means: a plugin may have registered extra field methods.

## Variants / aliases

- Field methods live on `Kirby\Content\Field`
- Common examples: `escape()`, `exists()`, `isEmpty()`, `or()`, `toBool()`, `toDate()`, `toString()`, `kt()`
- Extendable via plugin extension: `field-methods`

## Example

```php
<h1><?= $page->title()->escape() ?></h1>
<time datetime="<?= $page->date()->toDate('c') ?>">
  <?= $page->date()->toDate('Y-m-d') ?>
</time>
```

## MCP: Inspect/verify

- If you’re unsure which methods exist, use `kirby_online` for the exact method name (e.g. “field method kt”).
- Validate the real output (escaping/formatting) by rendering the page with `kirby_render_page`.
- If field methods behave unexpectedly, inspect the raw field value via `kirby_read_page_content` and then re-run the transformation in `kirby_eval`.
- To check whether a project adds custom field methods, inspect installed plugins with `kirby_plugins_index` and consult `kirby://extension/field-methods`.

## Related terms

- kirby://glossary/field
- kirby://glossary/kirbytext
- kirby://glossary/kirbytag
- kirby://glossary/plugin

## Links

- https://getkirby.com/docs/reference/templates/field-methods
- https://getkirby.com/docs/reference/plugins/extensions/field-methods
- https://getkirby.com/docs/guide/content/text-formatting
