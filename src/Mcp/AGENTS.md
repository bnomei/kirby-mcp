# MCP Layer Guidelines

## Mission

Maintain a stable and secure MCP surface: tools, resources, and completions for Kirby projects.

## System

- Tools live in `src/Mcp/Tools/` as public methods annotated with `#[McpTool]` and `#[McpToolIndex]`.
  `src/Mcp/ToolIndex.php` discovers them via reflection.
- `ServerFactory` supports profiles. The default `project` profile exposes the full project/runtime surface.
  The `global-reference` profile is projectless and must expose only reference/K.B./docs/search tools and static
  resources; keep `ServerProfile` allowlists and `ToolIndex` filtering aligned when adding reference-safe entries.
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
- Tool methods should accept `Mcp\Server\RequestContext` when they need session/client access (for example, structured output). Do not type-hint `ClientGateway` directly.
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
- Bind modern confirmation keys to all security-relevant operation inputs. If a retry supplies confirmation input for different arguments, return the marked dry-run preview (`confirmationStatus=stale_input_ignored`, `retryWithoutInputResponses=true`) instead of executing or adding persistent request state for the single-ask flow.
- Keep `kirby_run_cli_command` defaults minimal; prefer dedicated tools/resources over broad allowlist patterns (especially for `mcp:*` runtime wrappers).
- Return structured data; avoid `echo`/side effects from tools/resources.
- Treat query evaluation tools (e.g. `kirby_query_dot`) as sensitive; keep confirm gating and document default enablement/disable switches.
- Stdio handshake-era tool calls (except `kirby_init`) are init-guarded. HTTP calls are scope-authorized and may call
  `kirby_init` for audit/guidance but do not require it because remote clients may use a fresh session per tool call.
  Modern `2026-07-28` calls remain stateless.
- In `global-reference` mode, `kirby_init` must not require or discover a Kirby project. It should describe the
  reference-only scope and explicitly direct project work to a separate project-local MCP server.
- Init gating is session-scoped via `SessionInterface`; use `RequestContext` to access per-session state from tools when needed.
- Dump trace convenience is handshake-session scoped. Stateless calls must correlate explicitly with `traceId` or `path`; do not persist modern dump state.
- Provide tool output schemas via `#[McpTool(outputSchema: ...)]` (SDK v0.3+); keep `structuredContent` + JSON text in sync.
- Validate representative success payloads against declared output schemas, including nullable values and empty PHP maps
  that must serialize as JSON objects rather than arrays.
- Modern structured tool results may append resource links for concrete `kirby://` URIs from explicit semantic fields; preserve structured fields and JSON text, filter templates, and never scan arbitrary content.
- SDK v0.4 validates tool input before method execution and adds resource subscribe/unsubscribe handlers; when behavior depends on legacy-compatible inputs or mutable resources, reflect that in schemas and tests.
- SDK v0.5 exposes top-level `title` on tools/prompts; keep `#[McpTool(title: ...)]` and `#[McpPrompt(title: ...)]` populated and aligned with display titles.
- Prefer SDK v0.5 titled enum elicitation schemas for choice-style client prompts; keep legacy explicit parameters (e.g. `confirm=true`) working.
- SDK v0.6 renames `Mcp\Schema\Resource` to `ResourceDefinition` and removes the manual-registration flag from `Registry::registerResource()`; keep sized manual resources registered after discovery so they override discovered definitions.
- SDK v0.6 adds default Streamable HTTP middleware. `HttpMcpHandler` disables the transport's default CORS/DNS middleware so this repo's outer auth/origin/CORS/host controls remain authoritative.
- SDK v0.8 serves both handshake and modern/stateless protocol eras. Keep POST classification/validation in the SDK and retain custom validation for GET/SSE.
- Modern global-reference discovery/list results and immutable bundled resource reads may carry public cache hints. Keep project-specific, externally fetched, runtime, auth, session, user, and mutable results at the SDK default (`ttlMs: 0`, private), and do not leak modern hints onto handshake-era responses.
- Keep `kirby://tools` private with zero TTL: its contents depend on the current user's permissions.
- Supported older initialize revisions remain handshake-compatible; modern `2026-07-28` requests use stateless dispatch. Keep MCP logging disabled; diagnostics go to stderr.
- Diagnostic correlation may include only a validated W3C v00 `traceparent`; allow the native header through HTTP CORS, and never log `tracestate` or `baggage`.
- Successful mutations publish resource updates to the modern notification bus and retain direct handshake notifications for session subscribers. Require each subscribed resource URI's normal bearer scope. HTTP uses bounded local-filesystem sharing with portable per-file sequence cursors; it is not multi-host, NFS, durable, or exactly-once.
- Apply resource scopes to legacy subscribe/unsubscribe and recheck all stored subscription scopes against the current token before opening GET/SSE; a session is not an authorization grant.
- `kirby_ide_helpers_status` template/snippet PHPDoc warnings must be usage-aware: only PHP-code references to `$kirby`, `$site`, and `$page` require matching `@var` hints.
- `resources/list` entries must stay Codex-compatible plain descriptors: `uri`, `name`, `title`, `description`, and `mimeType` only. Strip descriptor-level `annotations`, `size`, `icons`, and `_meta` at list serialization time; keep richer metadata on `resources/read` contents when available.
- Resource and resource-template definitions should include MCP `title` values; keep attribute titles and sized manual `ResourceDefinition` titles aligned.
- HTTP `/mcp` requests are auth-gated before MCP protocol handling: validate Origin, reject query-string credentials, require Bearer auth, attach `oauth.*` request metadata, and enforce operation scopes. Scopes alone do not filter discovery; Kirby capability permissions do.
- OAuth and remote-token requests resolve an existing Kirby user and apply the hierarchical `bnomei.kirby-mcp` permission map to discovery and operations. Content-update commands impersonate that user. Carry identity per request through `ProjectContext` and the subprocess environment, never tool arguments, client metadata, or session state. Missing/deleted users must not fall back to superuser. Local stdio and loopback shared-token operations remain trusted. Do not claim per-target isolation or protection against permitted eval/generic CLI execution.
- Reject NUL-containing OAuth subjects before environment propagation. Select user binding alongside OAuth validation, including empty listener token overrides. Keep preview-only blueprint/schema inspection out of confirmed writes so dynamic blueprint permissions are evaluated during the impersonated mutation.
- Kirby HTTP exposure is an explicit copied route via `KirbyMcpRoutes::routes()` or `KirbyMcpRoute::handle()`; do not auto-register routes from this Composer dependency. Keep the route disabled by default through config and fail closed when enabled config is invalid.
- Route actions returned by `KirbyMcpRoutes::routes()` must remain non-static closures so Kirby can bind them to its route instance before invocation.
- `KirbyMcpRoutes::routes()` also exposes the optional built-in OAuth provider routes for Claude Desktop/Claude.ai custom connectors. Keep `http.oauthProvider.enabled` disabled by default; when enabled, clients, auth codes, refresh tokens, sessions, remembered consents, and signing keys must stay under `.kirby-mcp/oauth`. Consent must default to an explicit approve/deny step (`snippet`, with built-in form fallback); `auto` is only for trusted private deployments.
- Keep HTTP as a transport wrapper around the same MCP server surface as stdio, filtered by remote Kirby permissions. Scope failures use structured 403/`insufficient_scope` responses; Kirby permission failures use HTTP 403 independently of discovery filtering.
- HTTP session state is per MCP session and uses the `MCP-Session-Id` header contract across POST/GET/DELETE requests. Init gating, dump trace IDs, subscriptions, and confirm state must remain session-scoped.
- File-backed HTTP session stores should use `ServerFactory::HTTP_SESSION_TTL_SECONDS` and `ServerFactory` should pass explicit SDK v0.6 GC settings through `Builder::setSession()`.
- Shared-token HTTP auth is loopback/local-development only. The Kirby route must reject shared-token requests unless PHP reports `REMOTE_ADDR` as loopback and the request host is an exact loopback host (`localhost`, `::1`, or a valid IPv4 literal in `127.0.0.0/8`; never a DNS name that merely starts with `127.`). Public header-capable clients may use explicit `remote-token` auth with hashed token records, HTTPS for non-loopback requests, and normal scope checks. OAuth JWT validation and the built-in OAuth provider remain the Claude Desktop/Claude.ai custom connector path.
- Keep init/info payloads lean; omit heavy blobs like `composer.lock` from tool/resource outputs (composer audit does not return lock data).
