# MCP Layer Guidelines

## Mission

Maintain a stable and secure MCP surface: tools, resources, prompts, and completions for Kirby projects.

## System

- Tools live in `src/Mcp/Tools/` as public methods annotated with `#[McpTool]` and `#[McpToolIndex]`.
  `src/Mcp/ToolIndex.php` discovers them via reflection.
- Prompts live in `src/Mcp/Prompts/` as public methods annotated with `#[McpPrompt]`.
  `src/Mcp/PromptIndex.php` discovers them via reflection and exposes a fallback via `kirby://prompts` and `kirby://prompt/{name}`.
- Resources live in `src/Mcp/Resources/` and expose `kirby://...` URIs.
- Content field guides live in `kb/kirby/update-schema/` and are exposed via `kirby://fields/update-schema` and `kirby://field/{type}/update-schema`.
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
- All tool calls (except `kirby_init`) are init-guarded by `RequireInitForToolsHandler` and must prompt the client to call `kirby_init` first.
- Init gating is session-scoped via `SessionInterface`; use `RequestContext` to access per-session state from tools when needed.
- Logging level is session-scoped; read and set it via `LoggingState` using the active `SessionInterface` (`Protocol::SESSION_LOGGING_LEVEL`).
- Dump trace IDs are session-scoped; only use `DumpState` with the active `SessionInterface`.
- Provide tool output schemas via `_meta.outputSchema` until the PHP MCP SDK supports first-class `outputSchema`; keep `structuredContent` + JSON text in sync.
- Resource list entries should include MCP `annotations` (audience + priority) and `_meta.lastModified` when the data source is known; size-bearing resources are registered manually in `bin/kirby-mcp`.
- Keep init/info payloads lean; omit heavy blobs like `composer.lock` from tool/resource outputs (composer audit does not return lock data).
