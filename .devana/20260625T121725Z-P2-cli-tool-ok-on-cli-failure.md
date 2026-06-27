DEVANA-FINDING: v1
DEVANA-STATE: open | P2 | medium | security=no
DEVANA-KEY: src/Mcp/Tools/CliTools.php:139 | cli-tool-ok-on-cli-failure

# `kirby_run_cli_command` returns `ok: true` when the CLI failed or timed out

## Finding

After the allowlist approves a command, `CliTools::runCliCommand()` returns `ok: true` whenever the CLI process was started, even if `exitCode !== 0` or `timedOut === true`. Failure is only reflected in secondary fields (`success`, `exitCode`, `timedOut`).

## Violated Invariant Or Contract

MCP tool consumers typically treat top-level `ok` as the primary success signal. `CliResources` and `RuntimeCommandRunner` treat non-zero exit codes and timeouts as failures at the primary outcome layer.

## Oracle

- `CliTools::runCliInternal()` always sets `'ok' => true` after execution (line 311).
- `CliTools::runCliCommand()` branches on `ok`, not `success` (lines 96–155).
- `RuntimeCommandRunner::runMarkedJson()` skips JSON parsing when `exitCode !== 0` or `timedOut` (lines 57–60).

## Counterexample

Call `kirby_run_cli_command(command="roots", timeoutSeconds=5)` against a slow or hung Kirby boot.

- `KirbyCliRunner` returns `exitCode=124`, `timedOut=true`.
- Tool response: `ok: true`, `success: false`, `timedOut: true`, `message: "Command executed."`.

An agent that checks only `ok` treats the timeout as success.

## Why It Might Matter

Automations and agents can proceed with empty or partial stdout, miss stderr, or mis-report command health during slow environments or hung runtime commands.

## Proof

Caller/callee contract mismatch:

```
runCliInternal() → KirbyCliResult(timedOut=true, exitCode=124)
  → return ['ok' => true, 'cli' => ...]

runCliCommand()
  → if ok === true → success branch with ok: true
  → success computed separately as (exitCode === 0 && !timedOut)
```

## Counterevidence Checked

- `success`, `exitCode`, and `timedOut` are present for careful consumers.
- Policy denials correctly return `ok: false` before execution.
- Counterevidence does not make `ok` semantically aligned with CLI completion status.

## Suggested Next Step

Set top-level `ok` from the same predicate as `success`, or document `ok` as "dispatch succeeded" and rename/clarify in the output schema.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-25: open by Devana. Initial report written from static source inspection.

DEVANA-KEY: src/Mcp/Tools/CliTools.php:139 | cli-tool-ok-on-cli-failure
DEVANA-SUMMARY: open | P2 | medium | kirby_run_cli_command keeps ok true after CLI timeout or non-zero exit, so consumers checking only ok misread failures as success.