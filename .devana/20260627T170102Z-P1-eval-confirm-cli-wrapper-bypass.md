DEVANA-FINDING: v1
DEVANA-STATE: open | P1 | high | security=yes
DEVANA-KEY: src/Mcp/Tools/CliTools.php:289 | eval-confirm-cli-wrapper-bypass

# `kirby_run_cli_command` bypasses eval elicitation with `--confirm`

## Finding

`kirby_eval` requires MCP-level confirmation (`confirm=true` or client elicitation) before executing PHP. The same execution can be reached through `kirby_run_cli_command` by passing `mcp:eval` with a `--confirm` CLI flag, needing only `kirby-mcp:execute` scope and a `cli.allow` entry—no elicitation and no dedicated eval tool gate.

## Violated Invariant Or Contract

`kirby_eval` documents confirmation and elicitation requirements (`RuntimeTools::evalPhp()`). Eval is disabled by default and treated as a sensitive capability (`src/Mcp/AGENTS.md`). A parallel CLI wrapper path must not weaken those gates when eval is enabled in config.

## Oracle

`EvalPhp::run()` executes only when `$cli->arg('confirm') === true` (`EvalPhp.php:64-77`). `RuntimeTools::evalPhp()` adds elicitation via `shouldRunWithElicitedConfirm()` before appending `--confirm` (`RuntimeTools.php:1619-1646`). `CliTools::runCliCommand()` forwards normalized arguments directly to `KirbyCliRunner` without an eval-specific confirm gate (`CliTools.php:289-294`).

## Counterexample

Config: `eval.enabled=true`, `cli.allow` includes `mcp:eval`. HTTP bearer scopes: `kirby-mcp:execute` (no write/admin).

`tools/call kirby_run_cli_command` with:

```json
{
  "command": "mcp:eval",
  "arguments": ["return $kirby->site()->children()->count();", "--confirm"],
  "allowWrite": false
}
```

`KirbyCliAllowlistPolicy` allows via `matchedAllow` (`KirbyCliAllowlistPolicy.php:73-76`). `EvalPhp` executes because `--confirm` is present. `kirby_eval` with the same code but `confirm=false` would elicit or dry-run.

## Why It Might Matter

Operators who scope tokens to `execute` but rely on eval elicitation for human approval can be bypassed by any client that uses the generic CLI tool. Combined with `cli.allow` misconfiguration, this enables unattended code execution.

## Proof

**Cross-entry mismatch:** `kirby_eval` path enforces MCP confirm/elicitation; `kirby_run_cli_command` + `mcp:eval --confirm` does not.

**Dataflow:** HTTP execute-scoped token → `CliTools` → `KirbyCliRunner` argv `[mcp:eval, code, --confirm]` → `EvalPhp` confirm check satisfied at CLI layer only.

## Counterevidence Checked

Eval remains disabled unless `eval.enabled` or env flag is set. `cli.allow` must include `mcp:eval`. `kirby_eval` over HTTP still needs `EXECUTE` scope. These are configuration gates, not substitutes for the missing MCP confirm on the CLI wrapper path.

## Suggested Next Step

Reject `mcp:eval` / `mcp:query:dot` in `kirby_run_cli_command` unless the MCP request carries an explicit confirm flag recorded in session state, or require admin scope for eval-class commands regardless of CLI `--confirm`.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-27: open by Devana. Initial report written from static source inspection across all nine trails (`--all`).

DEVANA-KEY: src/Mcp/Tools/CliTools.php:289 | eval-confirm-cli-wrapper-bypass
DEVANA-SUMMARY: open | P1 | high | kirby_run_cli_command can run mcp:eval with --confirm and bypass kirby_eval elicitation when cli.allow permits it.