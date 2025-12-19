# Layout (aliases: `$layout`, `Kirby\Cms\Layout`)

## Meaning

A layout represents one layout “row” from a layout field. It contains columns; each column can contain blocks.

Layouts can also store settings/attributes (e.g. a CSS class), accessible via `$layout->attrs()` and sometimes via shortcut methods.

## In prompts (what it usually implies)

- “Get layout columns/blocks” means: `$layout->columns()` then `$column->blocks()`.
- “Layout id for anchors” means: `$layout->id()`.
- “Use layout settings” means: read from `$layout->attrs()` (or shortcuts like `$layout->class()` when present).

## Variants / aliases

- `$layout->columns()`
- `$layout->attrs()` (settings)
- `$layout->id()`

## Example

```php
<?php foreach ($page->layout()->toLayouts() as $layout): ?>
  <section id="<?= $layout->id() ?>" class="<?= $layout->attrs()->class() ?>">
    <!-- ... -->
  </section>
<?php endforeach ?>
```

## MCP: Inspect/verify

- Confirm the field really is a layout field by reading the blueprint via `kirby_blueprint_read` and `kirby://field/layout`.
- Inspect stored layout content via `kirby_read_page_content`.
- Use `kirby_eval` to inspect the layout structure (counts, ids, column widths) without rendering.
- Validate final output via `kirby_render_page`.

## Related terms

- kirby://glossary/layouts
- kirby://glossary/layout-field
- kirby://glossary/blocks
- kirby://glossary/block

## Links

- https://getkirby.com/docs/reference/objects/cms/layout
