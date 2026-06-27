DEVANA-FINDING: v1
DEVANA-STATE: fixed | P1 | high | security=yes
DEVANA-KEY: src/Mcp/Http/HttpScopePolicy.php:64 | http-cli-render-bypasses-runtime-scope

# HTTP `kirby_run_cli_command` can invoke `mcp:render` without `kirby-mcp:runtime` scope

## Finding

`mcp:render` is whitelisted in `KirbyCliAllowlistPolicy::DEFAULT_ALLOW`. `kirby_run_cli_command` requires only `EXECUTE` scope at the HTTP layer, while the dedicated `kirby_render_page` tool requires `RUNTIME`. An HTTP client scoped for execute but not runtime can render pages and read live CMS output through the CLI wrapper.

## Violated Invariant Or Contract

`HttpScopePolicy` maps runtime-backed read/render tools (`kirby_render_page`, `kirby_read_*`, `kirby_runtime_*`) to `RUNTIME` scope. The equivalent runtime CLI command should not be reachable with a strictly narrower token.

## Oracle

- `KirbyCliAllowlistPolicy::DEFAULT_ALLOW` includes `RuntimeCommands::RENDER` (`mcp:render`).
- `HttpScopePolicy::toolScopes()` line 64–69: `kirby_run_cli_command` → `EXECUTE`.
- `HttpScopePolicy::toolScopes()` line 91: tools matching `_render_` (e.g. `kirby_render_page`) → `RUNTIME`.
- `RuntimeTools::renderPage()` invokes the same `mcp:render` runtime command via `RuntimeCommandRunner`.

## Counterexample

HTTP token scopes: `["kirby-mcp:read", "kirby-mcp:execute"]` (no `kirby-mcp:runtime`).

1. `tools/call` → `kirby_render_page` → HTTP 403 `insufficient_scope`.
2. `tools/call` → `kirby_run_cli_command(command: "mcp:render", arguments: ["home"])` → passes scope check → returns rendered HTML/trace via CLI stdout / marked JSON.

## Why It Might Matter

Deployments that intentionally withhold `kirby-mcp:runtime` to block live CMS rendering and content reads can be bypassed through the execute-scoped generic CLI tool. Render output may include page content and debug traces.

## Proof

**Cross-entry mismatch:** Same runtime side effect (`mcp:render`) with different HTTP scope requirements depending on whether the dedicated tool or CLI wrapper is used.

**Control-flow:** `tools/call` params.name = `kirby_run_cli_command` → `requiredScopes` = `[EXECUTE]` → `hasScopes` true → `CliTools` → `KirbyCliAllowlistPolicy` matches `mcp:render` in `DEFAULT_ALLOW` → `KirbyCliRunner` executes runtime render.

## Counterevidence Checked

- `kirby_run_cli_command` still requires runtime commands to be installed on disk; this is an auth-scope bypass, not an install bypass.
- Stdio MCP has no scope layer; finding applies to HTTP transport and Kirby `/mcp` route.
- `cli.allow` config is not required; `mcp:render` is in the built-in default allow list.

## Suggested Next Step

Classify `kirby_run_cli_command` as `RUNTIME` when the resolved command matches runtime CLI wrappers (`mcp:*`), or add per-command scope elevation in `HttpScopeMiddleware` based on the `command` argument.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-27: fixed (extends the `toolCallScopes()` argument-aware logic added for `http-cli-allowwrite-bypasses-write-scope`). `HttpScopePolicy::toolCallScopes()` now also reads the `command` argument of `kirby_run_cli_command`: when it targets an `mcp:*` runtime wrapper (e.g. `mcp:render`, `mcp:page:content`), it adds `kirby-mcp:runtime` to the required scopes. An execute-only HTTP token can no longer render pages or read live CMS output through the generic CLI wrapper — it gets 403 like `kirby_render_page`/`kirby_read_*`. Added a `stringArgument()` helper and unit cases (`mcp:render` → `[EXECUTE, RUNTIME]`, `mcp:page:content` → `[EXECUTE, RUNTIME]`, non-mcp `help` → `[EXECUTE]`). phpstan clean. (eval-class `mcp:eval`/`mcp:query:dot` are separately blocked from the CLI wrapper entirely by `eval-confirm-cli-wrapper-bypass`.)

- 2026-06-27: open by Devana. Initial report written from static source inspection.

DEVANA-KEY: src/Mcp/Http/HttpScopePolicy.php:64 | http-cli-render-bypasses-runtime-scope
DEVANA-SUMMARY: fixed | P1 | high | HTTP EXECUTE-scoped tokens can render pages via kirby_run_cli_command mcp:render while kirby_render_page requires RUNTIME scope.