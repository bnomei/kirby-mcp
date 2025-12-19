# MCP Layer Guidelines

## Mission

Maintain a stable and secure MCP surface: tools, resources, prompts, and completions for Kirby projects.

## System

- Tools live in `src/Mcp/Tools/` as public methods annotated with `#[McpTool]` and `#[McpToolIndex]`.
  `src/Mcp/ToolIndex.php` discovers them via reflection.
- Prompts live in `src/Mcp/Prompts/` as public methods annotated with `#[McpPrompt]`.
  `src/Mcp/PromptIndex.php` discovers them via reflection and exposes a fallback via `kirby://prompts` and `kirby://prompt/{name}`.
- Resources live in `src/Mcp/Resources/` and expose `kirby://...` URIs.
- Content field guides live in `kb/content/fields/` and are exposed via `kirby://fields/update-schema` and `kirby://field/{type}/update-schema`.
- Blueprint/page content outputs may include `fieldSchemas` maps with `_schemaRef` pointers to both panel refs and update schemas.
- Command execution is routed through `src/Cli/` and guarded by `src/Mcp/Policies/`.
- `src/Mcp/ToolIndex.php` may add curated “instance” entries for common resource templates (e.g. `kirby://section/pages`) to improve `kirby_tool_suggest`; keep these aligned with the corresponding docs/index sources.

## Workflows

- Add/modify a tool:
  1. Implement in `src/Mcp/Tools/*` and keep the tool `name` (`kirby_*`) backward compatible when possible.
  2. Add/adjust completions in `src/Mcp/Completion/*` for any user-facing params.
  3. Add/adjust tests in `tests/Unit` (pure logic) or `tests/Integration` (runtime/CLI).
  4. Update `README.md` when tool names, params, or outputs change.
- If discovery/indexing looks stale, clear caches (`ToolIndex::clearCache()`) or restart the server.

## Guardrails

- Treat tool names, parameter schemas, and `kirby://...` URIs as public API; changes must be reflected in tests + docs.
- Any write-capable tool/command must be explicitly gated (allowlist + confirmation) and reviewed for abuse paths.
- Keep `kirby_run_cli_command` defaults minimal; prefer dedicated tools/resources over broad allowlist patterns (especially for `mcp:*` runtime wrappers).
- Return structured data; avoid `echo`/side effects from tools/resources.
- All tool calls (except `kirby_init`) are init-guarded by `RequireInitForToolsHandler` and must prompt the client to call `kirby_init` first.
