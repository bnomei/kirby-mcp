# Blocks field (aliases: `type: blocks`, “page builder”, “blocks editor”)

## Meaning

The **blocks field** is a Panel field type that stores structured content as a sequence of blocks (text, images, galleries, code, embeds, …). In templates, you typically convert the field value to a `Blocks` collection with `$field->toBlocks()`.

Blocks are rendered via block snippets, usually in `site/snippets/blocks/<type>.php`. You can customize core blocks and register custom blocks in plugins.

## In prompts (what it usually implies)

- “Add a blocks field to the blueprint” means: define `type: blocks` in the relevant blueprint.
- “Render blocks” means: call `toBlocks()` and either echo the collection or loop and render each block.
- “Custom block type” means: plugin work + matching snippet/controller/model logic.

## Variants / aliases

- Blueprint field: `type: blocks`
- Template parsing: `$field->toBlocks()`
- Custom block ecosystem:
  - block snippets (`snippets/blocks/*`)
  - plugin extensions: `blocks`, `block-methods`, `block-models`

## Example

```php
<?= $page->text()->toBlocks() ?>
```

## MCP: Inspect/verify

- Inspect the blueprint config that defines the blocks field:
  - `kirby_blueprint_read` / `kirby://blueprint/{encodedId}`
- Use the built-in Panel field reference to confirm options:
  - `kirby://field/blocks`
- Inspect real page content for the field value with `kirby_read_page_content`, then validate rendering with `kirby_render_page`.
- If blocks are plugin-provided/customized, inspect plugins with `kirby_plugins_index` and consult `kirby://extension/blocks`.

## Related terms

- kirby://glossary/block
- kirby://glossary/blocks
- kirby://glossary/snippet
- kirby://glossary/blueprint

## Links

- https://getkirby.com/docs/guide/page-builder
- https://getkirby.com/docs/reference/panel/fields/blocks
- https://getkirby.com/docs/reference/templates/field-methods/to-blocks
