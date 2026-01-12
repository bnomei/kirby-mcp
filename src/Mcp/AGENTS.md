# MCP Layer Guidelines

## Mission

Maintain a stable and secure MCP surface: tools, resources, and completions for Kirby projects.

## System

- Tools live in `src/Mcp/Tools/` as public methods annotated with `#[McpTool]` and `#[McpToolIndex]`.
  `src/Mcp/ToolIndex.php` discovers them via reflection.
- `McpToolIndex` keyword matching is token-based; avoid multi-word keywords and add single-token synonyms for tool suggestion queries (e.g. matrix/ratings/score).
- Prompt generators remain in `src/Mcp/Prompts/` (annotated with `#[McpPrompt]`) but are not registered with the MCP server.
- Resources live in `src/Mcp/Resources/` and expose `kirby://...` URIs.
- UUIDs for new content/blocks are generated via `kirby://uuid/new` using Kirby's UUID generator (no project-level uniqueness check).
- Content field guides live in `kb/update-schema/` and are exposed via `kirby://fields/update-schema` and `kirby://field/{type}/update-schema`.
- Blueprint update guides live in `kb/update-schema/blueprint-*.md` and are exposed via `kirby://blueprints/update-schema` and `kirby://blueprint/{type}/update-schema`.
- KB document list/read resources: `kirby://kb` and `kirby://kb/{path}` (path relative to `kb/`, no `.md`).
- Blueprint/page content outputs may include `fieldSchemas` maps with `_schemaRef` pointers to both panel refs and update schemas.
- Command execution is routed through `src/Cli/` and guarded by `src/Mcp/Policies/`.
- `src/Mcp/ToolIndex.php` may add curated “instance” entries for common resource templates (e.g. `kirby://section/pages`) to improve `kirby_tool_suggest`; keep these aligned with the corresponding docs/index sources.
- Tool methods should accept `Mcp\Server\RequestContext` when they need session/client access (logging, structured output). Do not type-hint `ClientGateway` directly.
- `DocsTools` and `OnlinePluginsTools` are intentionally extensible so tests can override their HTTP fetches; keep network calls out of unit tests.

## Workflows

- Add/modify a tool:
  1. Implement in `src/Mcp/Tools/*` and keep the tool `name` (`kirby_*`) backward compatible when possible.
  2. Add/adjust completions in `src/Mcp/Completion/*` for any user-facing params.
  3. Add/adjust tests in `tests/Unit` (pure logic) or `tests/Integration` (runtime/CLI).
  4. Update `README.md` when tool names, params, or outputs change.
- If discovery/indexing looks stale, clear caches (`ToolIndex::clearCache()`) or restart the server.

## Guardrails

- Treat tool names, parameter schemas, and `kirby://...` URIs as public API; changes must be reflected in tests + docs.
- Keep tool input schemas aligned with actual payload handling (e.g. `kirby_update_page_content.data` expects an object; JSON strings may be accepted for compatibility but should be parsed explicitly).
- Any write-capable tool/command must be explicitly gated (allowlist + confirmation) and reviewed for abuse paths.
- Keep `kirby_run_cli_command` defaults minimal; prefer dedicated tools/resources over broad allowlist patterns (especially for `mcp:*` runtime wrappers).
- Return structured data; avoid `echo`/side effects from tools/resources.
- Treat query evaluation tools (e.g. `kirby_query_dot`) as sensitive; keep confirm gating and document default enablement/disable switches.
- All tool calls (except `kirby_init`) are init-guarded by `RequireInitForToolsHandler` and must prompt the client to call `kirby_init` first.
- Init gating is session-scoped via `SessionInterface`; use `RequestContext` to access per-session state from tools when needed.
- Logging level is session-scoped; read and set it via `LoggingState` using the active `SessionInterface` (`Protocol::SESSION_LOGGING_LEVEL`).
- Dump trace IDs are session-scoped; only use `DumpState` with the active `SessionInterface`.
- Provide tool output schemas via `#[McpTool(outputSchema: ...)]` (SDK v0.3+); keep `structuredContent` + JSON text in sync.
- Resource list entries should include MCP `annotations` (audience + priority) and `_meta.lastModified` when the data source is known; size-bearing resources are registered manually in `bin/kirby-mcp`.
- Keep init/info payloads lean; omit heavy blobs like `composer.lock` from tool/resource outputs (composer audit does not return lock data).
