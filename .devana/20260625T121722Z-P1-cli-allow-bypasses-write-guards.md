DEVANA-FINDING: v1
DEVANA-STATE: fixed | P1 | high | security=yes
DEVANA-KEY: src/Mcp/Policies/KirbyCliAllowlistPolicy.php:76 | cli-allow-bypasses-write-guards

# `cli.allow` patterns bypass `allowWrite` and HTTP write-scope guards

## Finding

`KirbyCliAllowlistPolicy` treats any `cli.allow` match as sufficient authorization, even for write-capable commands that are supposed to require `allowWrite=true`. The same path also bypasses dedicated write-tool guards such as `payloadValidatedWithFieldSchemas` and HTTP `kirby-mcp:write` scope checks when operators whitelist `mcp:*` runtime wrappers in `cli.allow`.

## Violated Invariant Or Contract

`README.md` documents `cli.allowWrite` as requiring `allowWrite=true` on `kirby_run_cli_command`. Dedicated update tools require `payloadValidatedWithFieldSchemas=true` before mutating content. HTTP scope policy maps `kirby_update_*` tools to `WRITE`, while `kirby_run_cli_command` needs only `EXECUTE`.

## Oracle

- `README.md` option table for `cli.allowWrite` (requires `allowWrite=true`).
- `RuntimeTools::updatePageContent()` blocks without `payloadValidatedWithFieldSchemas` (lines 689–701).
- `HttpScopePolicy::toolScopes()` classifies `kirby_update_page_content` as `WRITE` and `kirby_run_cli_command` as `EXECUTE`.
- `tests/Integration/KirbyRunCliToolTest.php` shows default deny for `mcp:page:update`, but no test for explicit `cli.allow` override.

## Counterexample

Config:

```json
{"cli":{"allow":["mcp:page:update"]}}
```

Call `kirby_run_cli_command(command="mcp:page:update", arguments=["home", "--data={\"title\":\"X\"}", "--confirm"], allowWrite=false)` with an HTTP token scoped only for `kirby-mcp:execute` and `kirby-mcp:runtime`.

- Allowlist: allowed via `matchedAllow`, `allowWrite` not required.
- HTTP scopes: `tools/call` on `kirby_run_cli_command` passes with `EXECUTE` only; `kirby_update_page_content` would return `insufficient_scope`.
- Schema gate: `PageUpdate::run()` has no `payloadValidatedWithFieldSchemas` check; page content is written after `--confirm`.

## Why It Might Matter

Operators may believe HTTP write scopes and MCP schema attestation protect content mutations. A narrow `cli.allow` entry for an `mcp:*` wrapper can reopen write paths through the execute-scoped CLI tool.

## Proof

Control-flow / contract mismatch trace:

```
tools/call kirby_run_cli_command
  → KirbyCliAllowlistPolicy::evaluate()
      $allowed = ($matchedAllow !== null) || ($allowWrite && $matchedAllowWrite)
  → KirbyCliRunner::run("mcp:page:update", ..., "--confirm")
  → PageUpdate::run() → $page->update()
```

Parallel dedicated-tool path stops earlier on missing `payloadValidatedWithFieldSchemas` and HTTP `WRITE` scope.

## Counterevidence Checked

- Default built-in allowlists do not include `mcp:*`; explicit config is required.
- CLI `--confirm` is still required for actual writes.
- `cli.deny` is evaluated first and can block commands.
- Counterevidence does not restore `allowWrite`, HTTP write scope, or schema attestation on the CLI path.

## Suggested Next Step

Require `allowWrite=true` when a matched pattern is classified as write-capable (built-in `DEFAULT_ALLOW_WRITE`, `mcp:*:update`, etc.), or restrict `cli.allow` to read-only command classes.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-25: open by Devana. Initial report written from static source inspection.
- 2026-06-27: fixed. Added an intrinsic `WRITE_CAPABLE` classification (`make:*`, `clear:*`, `mcp:*:update`, `mcp:update`, `mcp:install`) to `KirbyCliAllowlistPolicy`. A command matching it (or an operator allowWrite pattern) now requires `allowWrite=true` AND a list match — a `cli.allow` entry alone no longer authorizes a write command. `KirbyCliAllowlistDecision` gained `matchedWriteCapable` and `requiresAllowWrite()` was corrected so the execute-scoped CLI tool returns "Command requires allowWrite=true." Added unit test for `cli.allow`-only `mcp:page:update`. Existing policy/CLI tests still pass; the 3 pre-existing `KirbyRunCliToolTest` failures are environment-only (fixture CMS not installed, CLI exit 255) and unchanged by this fix. phpstan clean.

DEVANA-KEY: src/Mcp/Policies/KirbyCliAllowlistPolicy.php:76 | cli-allow-bypasses-write-guards
DEVANA-SUMMARY: fixed | P1 | high | Whitelisting write commands in cli.allow lets kirby_run_cli_command mutate content without allowWrite, HTTP write scope, or payloadValidatedWithFieldSchemas.