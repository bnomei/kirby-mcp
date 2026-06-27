DEVANA-FINDING: v1
DEVANA-STATE: open | P1 | high | security=yes
DEVANA-KEY: src/Mcp/Http/RemoteTokenValidator.php:40 | remote-token-empty-scopes-grants-all

# Remote HTTP tokens with empty `scopes` silently receive all MCP scopes

## Finding

When a configured remote token has an empty `scopes` array, `RemoteTokenValidator` replaces it with `HttpAuthScopes::all()` (read, runtime, write, execute, admin). Operators who omit `scopes` expecting least-privilege or read-only access instead grant full HTTP MCP capabilities.

## Violated Invariant Or Contract

Configured token scopes should bound what the bearer can do. Empty scope lists typically mean "unspecified" or "deny until configured," not automatic elevation to every operation class including `ADMIN`.

## Oracle

- `RemoteTokenValidator::validate()` line 40: `$matchedToken->scopes === [] ? HttpAuthScopes::all() : $matchedToken->scopes`.
- `HttpAuthScopes::all()` returns all five scope constants used by `HttpScopeMiddleware`.
- `KirbyMcpHttpToken` records and config loading preserve `scopes: []` as an empty list (not null).

## Counterexample

`.kirby-mcp/mcp.json`:

```json
{
  "http": {
    "auth": {
      "mode": "remote-token",
      "tokens": [
        { "id": "ci-bot", "hash": "sha256:...", "scopes": [] }
      ]
    }
  }
}
```

Operator intent: register a token placeholder and tighten scopes later, or assume empty means read-only.

Runtime: Bearer for `ci-bot` receives `oauth.scopes` = all five scopes → can call `kirby_cache_clear` (ADMIN), `kirby_update_*` (WRITE), `kirby_render_page` (RUNTIME), and `kirby_run_cli_command` (EXECUTE).

## Why It Might Matter

Misconfigured production tokens are a common footgun. Silent full grant turns a missing config field into privilege escalation on the live MCP HTTP surface.

## Proof

**Dataflow:** Config `scopes: []` → `RemoteTokenValidator` → `AuthorizationResult::allow(['oauth.scopes' => HttpAuthScopes::all()])` → `HttpScopeMiddleware::hasScopes()` passes every operation.

**Contract mismatch:** Token record shape suggests optional scope list; validator interprets empty as universal grant.

## Counterevidence Checked

- Explicit non-empty `scopes` in config are honored verbatim.
- HTTPS is enforced for non-loopback remote-token mode in `KirbyMcpRoute`; elevation still matters once the token is presented.
- Shared-token and OAuth paths use different validators; this is specific to `remote-token` auth mode.
- Intentional "full access token" could set scopes explicitly to all values; empty is ambiguous but dangerous as default.

## Suggested Next Step

Treat empty `scopes` as invalid at config validation time, or default to a minimal scope set (e.g. read-only) and require explicit opt-in for write/execute/admin.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-27: open by Devana. Initial report written from static source inspection.

DEVANA-KEY: src/Mcp/Http/RemoteTokenValidator.php:40 | remote-token-empty-scopes-grants-all
DEVANA-SUMMARY: open | P1 | high | Remote HTTP tokens with an empty scopes array are upgraded to HttpAuthScopes::all(), granting full MCP HTTP capabilities by default.