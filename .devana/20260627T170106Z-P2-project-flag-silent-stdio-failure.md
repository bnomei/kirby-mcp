DEVANA-FINDING: v1
DEVANA-STATE: fixed | P2 | medium | security=no
DEVANA-KEY: bin/kirby-mcp:98 | project-flag-silent-stdio-failure

# `--project` detection failure starts stdio server silently on wrong root

## Finding

When `bin/kirby-mcp` is launched with `--project` but no Kirby project can be detected, the entrypoint deliberately emits no error and still starts the stdio MCP server. `ProjectContext` then falls back to the process current working directory, so tools operate against an unintended directory.

## Violated Invariant Or Contract

Explicit `--project` signals user intent to bind a Kirby project. Other subcommands (`install`, `http`, `ide:*`) fail closed with stderr when the root is unknown. The default stdio server path should not silently ignore a failed project bind.

## Oracle

`bin/kirby-mcp` sets `$projectFlagProvided = true` for `--project` variants (`bin/kirby-mcp:64-77`). On detection failure it skips `putenv` and suppresses output (`bin/kirby-mcp:98-102`). Contrast `install`/`http` paths that `exit(1)` with guidance (`bin/kirby-mcp:114-116`, `521-523`).

## Counterexample

Run `vendor/bin/kirby-mcp --project` from `/tmp` (no composer Kirby project). No stderr message. `kirby_init` / content tools use `getcwd()` as `projectRoot` via `ProjectContext::projectRoot()`.

## Why It Might Matter

IDE MCP configs commonly pass `--project`. Silent mis-binding causes destructive or misleading tool results against the wrong tree without an obvious failure signal on the stdio transport.

## Proof

**Control-flow trace:** `--project` + failed `ProjectRootFinder` → no env set → no error emission → `ServerFactory::create(PROFILE)` → `ProjectContext` cwd fallback.

## Counterevidence Checked

Stdio protocol discourages stderr noise during normal operation, but subcommands already write errors to stderr successfully. Auto-detect without `--project` intentionally allows cwd fallback; this finding applies only when the user supplied `--project`.

## Suggested Next Step

When `$projectFlagProvided === true` and detection fails, exit before starting stdio (or use a pre-flight env var gate) with a clear error on stderr; document that MCP clients must fix project configuration before reconnecting.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-27: open by Devana. Initial report written from static source inspection across all nine trails (`--all`).
- 2026-06-27: fixed. `bin/kirby-mcp` no longer silently swallows a failed `--project` auto-detect. When `$projectFlagProvided === true` and no composer-based Kirby root is found (and no explicit `--project=/path` was given, which sets the env earlier), it now writes a clear error to stderr and `exit(1)` before starting the stdio server — matching the fail-closed behavior of `install`/`http`. Writing to stderr is safe here because the server never starts, so the stdio protocol stream is not corrupted. Added integration test `it('fails closed when --project is given but no project root can be detected')` (runs the bin with `--project` from an empty temp cwd, `KIRBY_MCP_PROJECT_ROOT` unset → exit 1 + stderr message). Full `KirbyMcpBinCommandsTest` suite (10 tests) passes.

DEVANA-KEY: bin/kirby-mcp:98 | project-flag-silent-stdio-failure
DEVANA-SUMMARY: fixed | P2 | medium | Failed --project auto-detect silently starts stdio MCP against cwd instead of erroring like other subcommands.