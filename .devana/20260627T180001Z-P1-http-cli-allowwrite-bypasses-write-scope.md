DEVANA-FINDING: v1
DEVANA-STATE: open | P1 | high | security=yes
DEVANA-KEY: src/Mcp/Http/HttpScopePolicy.php:64 | http-cli-allowwrite-bypasses-write-scope

# HTTP `kirby_run_cli_command` `allowWrite` bypasses `kirby-mcp:write` scope

## Finding

`HttpScopePolicy` classifies `kirby_run_cli_command` as `EXECUTE` only. The tool's `allowWrite` parameter is evaluated inside `CliTools` by `KirbyCliAllowlistPolicy`, not by HTTP scope middleware. A token with `kirby-mcp:execute` but without `kirby-mcp:write` can run built-in write CLI patterns (`make:*`, `clear:*`) by passing `allowWrite=true`.

## Violated Invariant Or Contract

HTTP auth is documented to enforce operation scopes without hiding tools (`src/Mcp/AGENTS.md`). Write-capable Kirby CLI commands (`make:*`, `clear:*`) are gated behind `cli.allowWrite` and require `allowWrite=true` on the MCP tool. Equivalent dedicated update tools map to `WRITE` scope at the HTTP layer.

## Oracle

- `HttpScopePolicy::toolScopes()` lines 63–70: `kirby_run_cli_command` → `EXECUTE` only.
- `KirbyCliAllowlistPolicy::DEFAULT_ALLOW_WRITE` includes `make:*` and `clear:*`.
- `KirbyCliAllowlistPolicy::evaluate()` line 76: `allowWrite === true && matchedAllowWrite !== null` grants access.
- `HttpScopeMiddleware` checks only `oauth.scopes` from the token; it does not inspect tool argument `allowWrite`.

## Counterexample

HTTP Bearer token scopes: `["kirby-mcp:read", "kirby-mcp:execute"]` (no `kirby-mcp:write`).

`tools/call` → `kirby_run_cli_command` with `command: "clear:cache"`, `allowWrite: true`.

- Scope middleware: `kirby_run_cli_command` requires `EXECUTE` → passes.
- Allowlist: `clear:cache` matches `clear:*` in `DEFAULT_ALLOW_WRITE` → allowed.
- Kirby CLI mutates project cache/state.

Dedicated `kirby_update_*` tools would return HTTP 403 `insufficient_scope` for the same token.

## Why It Might Matter

Remote-token and OAuth deployments that scope tokens to read+execute for "safe" automation can still trigger destructive Kirby CLI writes through the generic CLI tool. Operators may assume HTTP `WRITE` scope is necessary for any mutation path.

## Proof

**Cross-entry mismatch:** `kirby_update_page_content` requires `WRITE` at HTTP layer; `kirby_run_cli_command` with `allowWrite=true` reaches `clear:*` / `make:*` with `EXECUTE` only.

**Dataflow:** `oauth.scopes` (no write) → `HttpScopeMiddleware::hasScopes()` passes → `CliTools::runCliInternal()` → `KirbyCliAllowlistPolicy::evaluate($command, allowWrite: true)` → `KirbyCliRunner::run()` → project mutation.

## Counterevidence Checked

- `cli.allow` / `cli.allowWrite` config extensions are a separate bypass (already reported as `cli-allow-bypasses-write-guards`); this path uses built-in `DEFAULT_ALLOW_WRITE` without operator config changes.
- Dedicated write tools still require `WRITE` scope; only the CLI wrapper path is affected.
- Stdio transport has no HTTP scopes (local trust model); bug is reachable on HTTP `/mcp` and Kirby route.

## Suggested Next Step

Require `kirby-mcp:write` in `HttpScopeMiddleware` (or `HttpScopePolicy`) when `tools/call` targets `kirby_run_cli_command` with `allowWrite=true`, or when the resolved command matches `allowWrite` patterns.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-27: open by Devana. Initial report written from static source inspection.

DEVANA-KEY: src/Mcp/Http/HttpScopePolicy.php:64 | http-cli-allowwrite-bypasses-write-scope
DEVANA-SUMMARY: open | P1 | high | HTTP tokens with EXECUTE but not WRITE can mutate the project via kirby_run_cli_command when allowWrite=true matches built-in make:* / clear:* patterns.