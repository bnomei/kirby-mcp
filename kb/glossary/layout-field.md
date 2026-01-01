# Layout field (aliases: `type: layout`, “multi-column blocks”)

## Meaning

The **layout field** is a Panel field type for building multi-column layouts. It stores layouts (rows) that contain columns, and each column contains blocks.

In templates, you typically convert a layout field to a `Layouts` collection with `$field->toLayouts()` and then loop layouts → columns → blocks.

## In prompts (what it usually implies)

- “Render a layout field” means: use `toLayouts()` and loop through the grid structure.
- “Access layout settings” means: use `$layout->attrs()` (and shortcuts like `$layout->class()`).
- “Pass layout to block snippet” means: call `snippet(..., ['layout' => $layout])` when rendering blocks manually.

## Variants / aliases

- Blueprint field: `type: layout`
- Template parsing: `$field->toLayouts()`
- Layouts contain columns; columns contain blocks

## Example

```php
<?php foreach ($page->layout()->toLayouts() as $layout): ?>
  <?php foreach ($layout->columns() as $column): ?>
    <div class="column" style="--span:<?= $column->span() ?>">
      <?= $column->blocks() ?>
    </div>
  <?php endforeach ?>
<?php endforeach ?>
```

## MCP: Inspect/verify

- Inspect the blueprint config that defines the layout field:
  - `kirby_blueprint_read` / `kirby://blueprint/{encodedId}`
- Use the built-in Panel field reference to confirm options:
  - `kirby://field/layout`
- For storage format + update payload guidance:
  - `kirby://field/layout/update-schema`
- Inspect real page content for the field value with `kirby_read_page_content`, then validate rendering with `kirby_render_page`.

## Related terms

- kirby://glossary/layout
- kirby://glossary/layouts
- kirby://glossary/blocks
- kirby://glossary/block
- kirby://glossary/blueprint

## Links

- https://getkirby.com/docs/guide/page-builder
- https://getkirby.com/docs/reference/panel/fields/layout
- https://getkirby.com/docs/reference/templates/field-methods/to-layouts
