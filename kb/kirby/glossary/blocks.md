# Blocks (aliases: `$blocks`, `Kirby\Cms\Blocks`)

## Meaning

`Blocks` is a collection of `Block` objects. You typically obtain it from a blocks field via `$field->toBlocks()`.

You can render all blocks by echoing the collection, or you can loop blocks to wrap or customize output.

## In prompts (what it usually implies)

- “Render all blocks” means: `<?= $field->toBlocks() ?>`.
- “Wrap each block” means: loop the blocks and echo each `$block`.
- “Custom block snippet” means: create/override `snippets/blocks/<type>.php`.

## Variants / aliases

- `$field->toBlocks(): Kirby\Cms\Blocks`
- `$blocks->first()`, `$blocks->filter(...)`, `$blocks->map(...)` (collection operations)
- Block rendering defaults to snippets per block type

## Example

```php
<?= $page->text()->toBlocks() ?>
```

## MCP: Inspect/verify

- If output differs from expectations, inspect both:
  - raw content (`kirby_read_page_content`)
  - rendered HTML (`kirby_render_page`)
- Use `kirby_eval` to check which block types are present.
- To inspect blocks field blueprint options, use `kirby_blueprint_read` and `kirby://field/blocks`.

## Related terms

- kirby://glossary/block
- kirby://glossary/blocks-field
- kirby://glossary/snippet

## Links

- https://getkirby.com/docs/reference/objects/cms/blocks
- https://getkirby.com/docs/reference/templates/field-methods/to-blocks
