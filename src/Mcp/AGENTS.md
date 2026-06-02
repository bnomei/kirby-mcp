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
- Keep tool input schemas aligned with actual payload handling (e.g. `kirby_update_page_content.data` accepts an object and a JSON string for compatibility; expose both types in schema and parse strings explicitly).
- Any write-capable tool/command must be explicitly gated (allowlist + confirmation) and reviewed for abuse paths.
- If you add MCP elicitation to a confirm-gated tool, keep explicit `confirm=true` support and preserve dry-run fallback when elicitation is unavailable/declined.
- Keep `kirby_run_cli_command` defaults minimal; prefer dedicated tools/resources over broad allowlist patterns (especially for `mcp:*` runtime wrappers).
- Return structured data; avoid `echo`/side effects from tools/resources.
- Treat query evaluation tools (e.g. `kirby_query_dot`) as sensitive; keep confirm gating and document default enablement/disable switches.
- All tool calls (except `kirby_init`) are init-guarded by `RequireInitForToolsHandler` and must prompt the client to call `kirby_init` first.
- Init gating is session-scoped via `SessionInterface`; use `RequestContext` to access per-session state from tools when needed.
- Logging level is session-scoped; read and set it via `LoggingState` using the active `SessionInterface` (`Protocol::SESSION_LOGGING_LEVEL`).
- Dump trace IDs are session-scoped; only use `DumpState` with the active `SessionInterface`.
- Provide tool output schemas via `#[McpTool(outputSchema: ...)]` (SDK v0.3+); keep `structuredContent` + JSON text in sync.
- SDK v0.4 validates tool input before method execution and adds resource subscribe/unsubscribe handlers; when behavior depends on legacy-compatible inputs or mutable resources, reflect that in schemas and tests.
- SDK v0.5 exposes top-level `title` on tools/prompts; keep `#[McpTool(title: ...)]` and `#[McpPrompt(title: ...)]` populated and aligned with display titles.
- Prefer SDK v0.5 titled enum elicitation schemas for choice-style client prompts; keep legacy explicit parameters (e.g. `confirm=true`) working.
- SDK v0.6 renames `Mcp\Schema\Resource` to `ResourceDefinition` and removes the manual-registration flag from `Registry::registerResource()`; keep sized manual resources registered after discovery so they override discovered definitions.
- SDK v0.6 adds default Streamable HTTP middleware. `HttpMcpHandler` keeps this repo's auth/origin/CORS wrapper authoritative and explicitly installs protocol-version validation in the SDK transport.
- Write tools that mutate content exposed via `kirby://...` resources should emit `notifications/resources/updated` for subscribed URIs (session-scoped subscriptions).
- Resource list entries should include MCP `annotations` (audience + priority) and `_meta.lastModified` when the data source is known; size-bearing resources are registered manually by `src/Mcp/ServerFactory.php`.
- Resource and resource-template definitions should include MCP `title` values; keep attribute titles and sized manual `ResourceDefinition` titles aligned.
- HTTP `/mcp` requests are auth-gated before MCP protocol handling: validate Origin, reject query-string credentials, require Bearer auth, attach `oauth.*` request metadata, and enforce operation scopes without hiding tools/resources.
- Kirby HTTP exposure is an explicit copied route via `KirbyMcpRoutes::routes()` or `KirbyMcpRoute::handle()`; do not auto-register routes from this Composer dependency. Keep the route disabled by default through config and fail closed when enabled config is invalid.
- `KirbyMcpRoutes::routes()` also exposes the optional built-in OAuth provider routes for Claude Desktop/Claude.ai custom connectors. Keep `http.oauthProvider.enabled` disabled by default; when enabled, clients, auth codes, refresh tokens, sessions, remembered consents, and signing keys must stay under `.kirby-mcp/oauth`. Consent must default to an explicit approve/deny step (`snippet`, with built-in form fallback); `auto` is only for trusted private deployments.
- Keep HTTP as a transport wrapper around the same MCP server surface as stdio. Do not remove tools/resources for scoped clients; fail unauthorized operations with structured 403/`insufficient_scope` responses.
- HTTP session state is per MCP session and uses the `MCP-Session-Id` header contract across POST/GET/DELETE requests. Init gating, logging level, dump trace IDs, subscriptions, and confirm state must remain session-scoped.
- File-backed HTTP session stores should use `ServerFactory::HTTP_SESSION_TTL_SECONDS` and `ServerFactory` should pass explicit SDK v0.6 GC settings through `Builder::setSession()`.
- Shared-token HTTP auth is loopback/local-development only. The Kirby route must reject shared-token requests unless PHP reports `REMOTE_ADDR` as loopback. Public header-capable clients may use explicit `remote-token` auth with hashed token records, HTTPS for non-loopback requests, and normal scope checks. OAuth JWT validation and the built-in OAuth provider remain the Claude Desktop/Claude.ai custom connector path.
- Keep init/info payloads lean; omit heavy blobs like `composer.lock` from tool/resource outputs (composer audit does not return lock data).
