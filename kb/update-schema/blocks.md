# Blocks field (type: blocks)

## Field summary

Blocks field stores structured content blocks as a JSON array.

## Storage format

```json
[
  {
    "id": "dbef763a-2a53-4e51-80a7-04c0a1ebc897",
    "type": "heading",
    "isHidden": false,
    "content": {
      "level": "h2",
      "text": "Hello"
    }
  },
  {
    "id": "98b70f61-81d6-4774-b9dc-9c9502a12587",
    "type": "text",
    "isHidden": false,
    "content": {
      "text": "<p>Welcome</p>"
    }
  }
]
```

## Runtime value

Use `$page->text()->toBlocks()` to get a `Blocks` collection for rendering.

## Update payload (kirby_update_page_content)

```json
{ "text": [{ "id": "...", "type": "text", "isHidden": false, "content": { "text": "<p>...</p>" } }] }
```

## Merge strategy

Prefer full replace. For partial edits, read existing blocks, modify by block `id`, then write back.

## Edge cases

Each block needs a stable `id`. The `content` shape depends on the block blueprint.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/blocks`.
- Generate new block `id` values via `kirby://uuid/new` when creating blocks.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content
- kirby://glossary/blocks-field
- kirby://glossary/block

## Links

- https://getkirby.com/docs/reference/panel/fields/blocks
