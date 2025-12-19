# Field (aliases: `Kirby\Content\Field`, `$page->title()`, “content field”)

## Meaning

A Kirby “field” is the smallest unit of content on a model (page/site/file/user). In templates, you usually access fields via dynamic methods named after the field key, e.g. `$page->headline()` or `$site->description()`.

These calls return a `Kirby\Content\Field` object, which you then transform/output with [field methods](kirby://glossary/field-method) (e.g. `escape()`, `kt()`, `toDate()`, …).

## In prompts (what it usually implies)

- “The `text` field” means the content value stored under the `text:` key in the page’s content file(s).
- “Why does `$page->image()` not work?” might be a **naming conflict**: `image()` is also a page method. In that case access the field via `$page->content()->get('image')` / `$page->content()->image()`.
- “Add a field to the Panel” might mean a **blueprint field type** (see kirby://glossary/blueprint), not a content field.

## Variants / aliases

- `Kirby\Content\Field` (class)
- `$page->yourField()`, `$site->yourField()`, `$file->yourField()` (model fields)
- `$model->content()->get('yourField')` (conflict-safe access)
- Field values can be plain text, YAML, JSON, Markdown/KirbyText, etc.

## Example

```php
<h1><?= $page->headline()->or($page->title())->escape() ?></h1>
<?= $page->text()->kt() ?>
```

## MCP: Inspect/verify

- Use `kirby_read_page_content` (or `kirby://page/content/{encodedIdOrUuid}`) to inspect the current field values of a page.
- Use `kirby_eval` when you need conflict-safe access or want to check existence:
  - example: `return $page->content()->get('image')->value();`
- For Panel field types and their options, use the built-in docs resources:
  - `kirby://fields` and `kirby://field/{type}`

## Related terms

- kirby://glossary/field-method
- kirby://glossary/blueprint
- kirby://glossary/kirbytext
- kirby://glossary/kirbytag

## Links

- https://getkirby.com/docs/guide/content/fields
- https://getkirby.com/docs/reference/templates/field-methods
- https://getkirby.com/docs/reference/objects/content/field/__construct
