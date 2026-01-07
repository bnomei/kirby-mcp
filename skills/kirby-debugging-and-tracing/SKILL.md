---
name: kirby-debugging-and-tracing
description: Diagnose Kirby rendering/runtime issues using MCP runtime rendering, dump traces, and template/snippet/controller indexes. Use when outputs are wrong, errors occur, or you need to trace execution paths.
---

# Kirby Debugging and Tracing

## Quick start

- Follow the workflow below to reproduce and trace render issues.

## Workflow

1. Ask for page id/uuid or URL path, expected vs actual output, content type, and any session/login requirements.
2. Call `kirby_init`, then ensure runtime availability with `kirby_runtime_status` and `kirby_runtime_install` if needed.
3. Reproduce with `kirby_render_page(noCache=true, contentType=...)` and capture `traceId` plus errors.
4. Locate relevant code paths:
   - `kirby_templates_index`
   - `kirby_snippets_index`
   - `kirby_controllers_index`
   - `kirby_models_index`
   - `kirby_routes_index` when routing is involved
5. If the issue is unclear, add targeted `mcp_dump()` calls and read the trace with `kirby_dump_log_tail(traceId=...)`.
6. Apply the smallest fix, re-render to confirm, and remove temporary dumps.
7. Search the KB with `kirby_search` (examples: "custom routes", "snippet controllers", "shared controllers", "content representations").
