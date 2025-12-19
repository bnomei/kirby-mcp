# API (aliases: “Kirby REST API”, “Panel API”, `/api`, `/rest`)

## Meaning

Kirby’s REST API is used by the Panel (Vue frontend) to manage pages, files, users, etc. It can also be used by custom clients (SPA/mobile) and can be extended with custom endpoints.

The REST API is authenticated (session-based or HTTP Basic). Kirby also supports relocating the API (e.g. to `/rest`).

## In prompts (what it usually implies)

- “Build a headless frontend” may mean: use content representations, the REST API, KQL, or custom routes — pick based on whether you need write access and auth.
- “Update content via API” means: REST API (write) or MCP tools (project-local automation).
- “API returns error” means: check debug mode, auth, language header (`X-Language`), and API location config.

## Variants / aliases

- REST API (write-capable, authenticated)
- Alternatives for “headless” use cases:
  - [content representations](kirby://glossary/content-representation)
  - kirby://glossary/kql
  - kirby://glossary/route

## Example

```text
GET /api/pages
X-Language: en
```

## MCP: Inspect/verify

- Prefer project-local inspection first:
  - read content via `kirby_read_page_content`
  - verify output via `kirby_render_page`
- Check API-related config (runtime install required):
  - `kirby://config/api`
  - `kirby://config/debug`
- If a project uses custom API endpoints implemented as routes, locate them with `kirby_routes_index(patternContains='…')` (requires `kirby_runtime_install`).
- Use `kirby_online` to quickly jump to the exact API endpoint docs you need (“API endpoint reference pages files update”, etc.).

## Related terms

- kirby://glossary/kql
- kirby://glossary/content-representation
- kirby://glossary/route
- kirby://glossary/plugin

## Links

- https://getkirby.com/docs/guide/api
- https://getkirby.com/docs/reference/api
- https://getkirby.com/docs/reference/system/options/api
- https://getkirby.com/docs/reference/plugins/extensions/api
