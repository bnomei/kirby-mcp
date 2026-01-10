# UUID (aliases: `page://…`, `file://…`, `user://…`, “permalink”)

## Meaning

Kirby UUIDs are stable identifiers for models (pages, files, users, etc.) that stay the same even if you move or rename content. This makes them ideal for persistent relations (linking/embedding) compared to paths and filenames.

In Kirby, UUID strings typically look like `page://Eesj89FnbMzMMvs0` (or similar for files/users). Picker fields (`pages`, `files`, `users`) store UUIDs by default unless you disable UUID storage with the field’s `store` property. UUID generation can be configured (including UUID v4) or disabled entirely; the setting only affects newly generated UUIDs.

## In prompts (what it usually implies)

- “Use UUIDs so links survive renames/moves” means: store UUIDs in fields and resolve them back to models at runtime.
- “Why are there duplicate UUIDs?” often means: someone duplicated content folders manually in the filesystem (UUIDs got copied) instead of duplicating via the Panel (which regenerates UUIDs).
- “Fetch page/file by UUID” means: use APIs/tools that accept UUIDs (preferred) instead of guessing ids/paths.
- “We need permalinks” means: use the built-in `@/page/<uuid>` style URLs or `$model->permalink()` to generate stable links.

## Variants / aliases

- UUID schemes: `page://…`, `file://…`, `user://…`
- Permalink path format: `@/page/<uuid>` (pages; use `$model->permalink()` for other model types)
- `$model->uuid()->toString()` (get UUID string)
- Config (affects newly generated UUIDs only):
  - `content.uuid: 'uuid-v4'`
  - `content.uuid: false`

## Example

```php
// store or compare stable identifiers
$uuid = $page->uuid()->toString();

// generate a stable permalink for sharing
$permalink = $page->permalink();
```

## MCP: Inspect/verify

- Read UUID-related config (runtime install required):
  - `kirby://config/content.uuid`
- Fetch a page by UUID (runtime install required):
  - `kirby_read_page_content` with `id: <uuid>`
  - or `kirby://page/content/{encodedIdOrUuid}` (UUID can be passed as raw UUID or encoded `page%3A%2F%2F…`)
- If you suspect duplicates, check the CLI inventory first:
  - `kirby://commands` then `kirby://cli/command/uuid:duplicates`
- After changing config or installing runtime commands, clear MCP caches with `kirby_cache_clear` to avoid stale reads.

## Related terms

- kirby://glossary/page
- kirby://glossary/file
- kirby://glossary/id
- kirby://glossary/uid
- kirby://glossary/slug
- kirby://glossary/uri
- kirby://glossary/content
- kirby://glossary/cache

## Links

- https://getkirby.com/docs/guide/uuids
- https://getkirby.com/docs/reference/system/options/content#uuid-generation
- https://getkirby.com/docs/reference/objects/uuid/uuid
