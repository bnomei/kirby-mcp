# Block (aliases: `$block`, `Kirby\Cms\Block`)

## Meaning

A block is one item inside a blocks/layout field. Each block has an id and a type and can be rendered to HTML (often via a snippet).

When you loop blocks, you typically work with `$block` objects; echoing a `$block` renders its corresponding snippet.

## In prompts (what it usually implies)

- “If block type is X” means: check `$block->type()` and branch accordingly.
- “Render block HTML” means: echo the block or call a snippet manually.
- “Access block content” means: use `$block->content()` (or specific helpers/methods) to read its fields.

## Variants / aliases

- `$block->id()`, `$block->type()`
- `<?= $block ?>` (render default snippet)
- `snippet('blocks/' . $block->type(), ['block' => $block])` (manual rendering)

## Example

```php
<?php foreach ($page->text()->toBlocks() as $block): ?>
  <div id="<?= $block->id() ?>" class="block block-type-<?= $block->type() ?>">
    <?= $block ?>
  </div>
<?php endforeach ?>
```

## MCP: Inspect/verify

- Inspect which field/blueprint controls blocks via `kirby_blueprint_read` and `kirby://field/blocks`.
- Inspect actual stored content via `kirby_read_page_content` (blocks are stored as YAML/JSON in a field).
- Use `kirby_eval` to quickly list block ids/types for a page without rendering:
  - example: `return $page->text()->toBlocks()->map(fn ($b) => [$b->id(), $b->type()])->values();`
- Verify final HTML output in context with `kirby_render_page`.

## Related terms

- kirby://glossary/blocks
- kirby://glossary/blocks-field
- kirby://glossary/snippet
- kirby://glossary/layout

## Links

- https://getkirby.com/docs/reference/objects/cms/block
