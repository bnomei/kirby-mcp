# Layout field (type: layout)

## Field summary

Layout field stores rows, columns, and nested blocks as a JSON array. Block content is defined by block blueprints; layout settings map to `attrs`.

## Storage format

```json
[
  {
    "id": "d34c0490-20b0-43bd-ac79-e79f8d760e80",
    "attrs": [],
    "columns": [
      {
        "id": "d33ca1fe-ba51-4af0-bd3c-c3aefed1ae97",
        "width": "1/1",
        "blocks": [
          {
            "id": "ab524415-3a32-4d2b-ba2d-9d2272362138",
            "type": "text",
            "isHidden": false,
            "content": { "text": "<p>...</p>" }
          }
        ]
      }
    ]
  }
]
```

## Runtime value

Use `$page->layout()->toLayouts()` to loop rows -> columns -> blocks.

## Update payload (kirby_update_page_content)

```json
{ "layout": [{ "id": "...", "attrs": [], "columns": [{ "id": "...", "width": "1/1", "blocks": [] }] }] }
```

## Merge strategy

Prefer full replace. For partial edits, read existing layouts, modify by layout/column `id`, then write back.

## Edge cases

Row `attrs` stores layout settings (keys reflect your blueprint `settings`). Keep `id` values stable.

## MCP: Inspect/verify

- Read the blueprint config via `kirby_blueprint_read` or `kirby://blueprint/{encodedId}`.
- Inspect block blueprints (`blocks/<type>`) with `kirby_blueprints_index` and `kirby_blueprint_read`.
- Inspect stored values with `kirby_read_page_content`.
- Confirm Panel options via `kirby://field/layout`.
- Generate row/column/block `id` values via `kirby://uuid/new` when creating layouts.

## Glossary quick refs

- kirby://glossary/field
- kirby://glossary/content
- kirby://glossary/layout-field
- kirby://glossary/layout

## Links

- https://getkirby.com/docs/reference/panel/fields/layout
- https://getkirby.com/docs/reference/panel/blocks
- https://getkirby.com/docs/reference/panel/blueprints/block
