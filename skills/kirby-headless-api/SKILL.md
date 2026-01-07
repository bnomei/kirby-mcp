---
name: kirby-headless-api
description: Expose Kirby content to headless clients using the API, KQL, and JSON representations. Use when building API endpoints, KQL queries, or headless frontends.
---

# Kirby Headless API

## Workflow

1. Clarify consumers, authentication, and which content is public vs private.
2. Call `kirby_init` and read `kirby://config/api` to confirm API settings.
3. Check plugin availability for KQL: `kirby_plugins_index`.
4. If you need custom endpoints, inspect existing routes with `kirby_routes_index` (install runtime if needed).
5. Search the KB with `kirby_search` (examples: "headless api with kql", "json content representation", "figma auto populate", "headless kiosk").
6. Use `kirby_online` to fetch official API/KQL docs when KB coverage is insufficient.
7. Implement:
   - enable API auth (`api.basicAuth`) when required
   - create or update KQL queries for `/api/query`
   - add `.json` representations for template-backed JSON
8. Verify:
   - request `/api/query` with Basic Auth
   - render `.json` representations with `kirby_render_page(contentType: json)`
