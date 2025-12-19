# Layouts (aliases: `$layouts`, `Kirby\Cms\Layouts`)

## Meaning

`Layouts` is a collection of `Layout` objects (from a layout field). You typically obtain it via `$field->toLayouts()`.

You render layouts by looping layouts → columns → blocks. Depending on your needs you can let Kirby render all blocks in a column automatically (`<?= $column->blocks() ?>`) or render each block manually (and pass extra data to snippets).

## In prompts (what it usually implies)

- “Render multi-column content” means: use a layout field and loop through layouts/columns/blocks.
- “Customize block rendering in layout” means: nested loops and manual `snippet('blocks/' . $block->type(), ['block' => $block, 'layout' => $layout])`.

## Variants / aliases

- `$field->toLayouts(): Kirby\Cms\Layouts`
- `$layout->columns()` and `$column->blocks()`

## Example

```php
<?php foreach ($page->layout()->toLayouts() as $layout): ?>
  <?php foreach ($layout->columns() as $column): ?>
    <?= $column->blocks() ?>
  <?php endforeach ?>
<?php endforeach ?>
```

## MCP: Inspect/verify

- Inspect blueprint options with `kirby_blueprint_read` and `kirby://field/layout`.
- Inspect stored content with `kirby_read_page_content`.
- Validate rendering with `kirby_render_page` (use `noCache=true` if needed).

## Related terms

- kirby://glossary/layout
- kirby://glossary/layout-field
- kirby://glossary/blocks

## Links

- https://getkirby.com/docs/reference/objects/cms/layouts
- https://getkirby.com/docs/reference/templates/field-methods/to-layouts
