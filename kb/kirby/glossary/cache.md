# Cache (aliases: “Kirby cache”, “page cache”, “clear cache”, “media cache”)

## Meaning

Kirby uses caching for performance (page cache, data caches, thumbnails/media, etc.) and lets you configure cache drivers and per-cache settings.

Separately, this MCP server keeps in-memory caches for faster tool responses (CLI parsing, roots inspection, etc.). Clearing MCP caches is not the same as clearing Kirby’s project caches.

## In prompts (what it usually implies)

- “Clear the cache” might mean:
  - clear Kirby’s caches (project-level), or
  - clear the MCP server’s in-memory caches (tooling-level), or
  - both if debugging stale output.
- “Disable page cache” means: configure `cache.pages.active`.

## Variants / aliases

- Config: `cache.pages.active`, driver types, cache prefix, per-cache settings (e.g. `cache.uuid`)
- Kirby CLI has cache clearing commands (discover via `kirby://commands`)

## Example

```php
return [
  'cache' => [
    'pages' => [
      'active' => true,
    ],
  ],
];
```

## MCP: Inspect/verify

- Clear MCP server caches (does not touch project files): `kirby_cache_clear`.
- Read Kirby cache config (runtime install required):
  - `kirby://config/cache`
- Discover cache-clearing CLI commands:
  - `kirby://commands` and `kirby://cli/command/clear:cache`
- If rendered output seems stale, render with `kirby_render_page` using `noCache=true` and/or clear project caches via CLI (only when explicitly requested).

## Related terms

- kirby://glossary/kirby
- kirby://glossary/content
- kirby://glossary/uuid

## Links

- https://getkirby.com/docs/guide/cache
- https://getkirby.com/docs/reference/system/options/cache
