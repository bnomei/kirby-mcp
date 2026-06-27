DEVANA-FINDING: v1
DEVANA-STATE: open | P1 | high | security=yes
DEVANA-KEY: src/Mcp/Http/HttpScopePolicy.php:22 | http-resources-read-scope-bypass

# HTTP `resources/read` bypasses runtime scope tier

## Finding

`HttpScopePolicy` maps every `resources/read` call to `kirby-mcp:read`, ignoring the target URI. A bearer token with only the read scope can invoke live Kirby runtime reads (page content, file content, config options, blueprint data) through resource templates, while the equivalent `tools/call` paths require `kirby-mcp:runtime` or higher.

## Violated Invariant Or Contract

HTTP scope enforcement is supposed to gate sensitive operations without hiding the MCP surface (`src/Mcp/AGENTS.md`). `kirby_read_page_content` requires `RUNTIME`, but `resources/read` on `kirby://page/content/{id}` requires only `READ`. Operators issuing read-only tokens reasonably expect runtime CMS data to stay blocked.

## Oracle

`tests/Unit/McpHttpScopePolicyTest.php` classifies `tools/call` + `kirby_read_page_content` as `RUNTIME` but `resources/read` (any URI) as `READ`. `tests/Integration/KirbyMcpServerHttpAuthTest.php` blocks write/execute/admin tool calls with a read-only token but never tests runtime resource URIs.

## Counterexample

Bearer token scopes: `["kirby-mcp:read"]`. After initialize, send:

```json
{"jsonrpc":"2.0","method":"resources/read","id":2,"params":{"uri":"kirby://page/content/home"}}
```

`HttpScopeMiddleware` requires only `READ` and passes. `PageResources::pageContent()` runs `mcp:page:content` via `RuntimeCommandRunner`, returning live page fields. The same token receives 403 on `tools/call` + `kirby_read_page_content`.

## Why It Might Matter

Read-scoped OAuth or remote tokens can exfiltrate CMS content, blueprints, and config values that operators intended to reserve for runtime-scoped clients. This undermines least-privilege HTTP auth configuration.

## Proof

**Contract mismatch:** `HttpScopePolicy::requiredScopes()` returns `[READ]` for `resources/read` without inspecting `params.uri` (`HttpScopePolicy.php:22-28`). `toolScopes('kirby_read_page_content')` returns `[RUNTIME]` (`HttpScopePolicy.php:83-92`).

**Dataflow:** read-only bearer → `HttpScopeMiddleware` (READ passes) → `PageResources::pageContent()` → `RuntimeCommandRunner::runMarkedJson(... PAGE_CONTENT ...)` → Kirby CLI runtime command.

## Counterevidence Checked

`resources/list` intentionally stays READ-scoped to keep discovery visible. That does not require runtime reads to share the same tier. `KirbyMcpServerHttpAuthTest` only asserts `resources/list` succeeds with a read token, not runtime URIs. No URI-based scope routing exists elsewhere in `HttpScopePolicy`.

## Suggested Next Step

Map `resources/read` URIs to the same scope tiers as their tool equivalents (e.g. `kirby://page/content/*` → `RUNTIME`, `kirby://config/*` → `RUNTIME`, static `kirby://kb/*` → `READ`). Add an integration test mirroring the existing read-token tool-call denials.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-27: open by Devana. Initial report written from static source inspection across all nine trails (`--all`).

DEVANA-KEY: src/Mcp/Http/HttpScopePolicy.php:22 | http-resources-read-scope-bypass
DEVANA-SUMMARY: open | P1 | high | Read-scoped HTTP tokens can read live CMS content via resources/read while the equivalent tools require runtime scope.