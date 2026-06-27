DEVANA-FINDING: v1
DEVANA-STATE: fixed | P2 | medium | security=no
DEVANA-KEY: src/Mcp/Tools/RuntimeTools.php:373 | runtime-install-missing-ok-signal

# `kirby_runtime_install` omits `ok: false` when install records partial errors

## Finding

`RuntimeCommandsInstaller::install()` can finish with a non-empty `errors` array (per-file failures) while still returning some `installed` paths. `RuntimeTools::runtimeInstall()` forwards `RuntimeCommandsInstallResult::toArray()` directly, which has no top-level `ok` field. MCP clients that treat missing `ok` as success can proceed after a broken install.

## Violated Invariant Or Contract

Destructive/setup tools should expose an unambiguous success/failure signal. `bin/kirby-mcp install` computes `'ok' => $installResult->errors === [] && ...`; the MCP tool should match that contract.

## Oracle

- `RuntimeCommandsInstallResult::toArray()` returns only `projectRoot`, `commandsRoot`, `installed`, `skipped`, `errors` (no `ok`).
- `RuntimeTools::runtimeInstall()` line 373: `return $this->maybeStructuredResult($context, $result->toArray())`.
- `bin/kirby-mcp` install payload line 234: explicit `ok` boolean.
- Installer loop continues after per-file errors (non-transactional multi-file install).

## Counterexample

Pre-state: `site/commands/mcp` exists as a **file** (not directory), blocking one target path.

1. `kirby_runtime_install(force: true)` runs installer.
2. Some files install; `errors` contains `{path, error}` entries.
3. Tool returns `{installed: [...], errors: [...]}` without `ok: false`.
4. Agent checks only `installed.length > 0` (or absent `ok`) and calls `kirby_render_page` → `needsRuntimeInstall` or parse errors.

## Why It Might Matter

Automated agents rely on structured `ok` flags across MCP tools (`CliTools`, `ProjectTools` adapters differ). Silent partial failure wastes downstream tool calls and leaves projects half-configured.

## Proof

**Contract mismatch:** Same installer, different adapters—CLI entrypoint sets `ok`, MCP tool does not.

**Control-flow:** `errors !== []` → still returns normal tool result (no exception, no `ok: false`).

## Counterevidence Checked

- `kirby_runtime_status` can detect `missingFiles` and partial install messaging if called separately; not enforced by install tool.
- `errors` array is present for diligent consumers; issue is ambiguous success signaling.
- Full failure with zero installs still lacks `ok: false` (same contract gap).

## Suggested Next Step

Map `errors === []` to `ok: true` and non-empty `errors` to `ok: false` in `runtimeInstall()` (and align `RuntimeCommandsInstallResult::toArray()` if shared elsewhere).

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-27: fixed. `RuntimeCommandsInstallResult::toArray()` now includes a top-level `ok` boolean (as the first key) derived from a new `ok()` method (`errors === []`). Since `kirby_runtime_install` forwards `toArray()` verbatim, the MCP tool now exposes an unambiguous success/failure signal: a partial or total install failure (e.g. `site/commands/mcp` exists as a file, blocking a target path) returns `ok: false` alongside the `errors` array, so agents that key on `ok` no longer treat a broken install as success and proceed to `kirby_render_page`. Aligned `bin/kirby-mcp` to compute its payload `ok` via `$installResult->ok() && $configResult['error'] === null` (single source of truth for the per-file part; bin additionally factors the config-install step). The added key is additive — integration setups that read `$install['commandsRoot']` are unaffected. Extended `RuntimeCommandsInstallerTest`: the blocked-install case now asserts `ok()`/`toArray()['ok']` are false, plus a new happy-path test asserting `ok()` true and `ok` is the first key. phpstan clean on `src`/`tests` (the pre-existing `bin/kirby-mcp` undefined-`$argv`/literal-comparison warnings are outside the analyzed paths and predate this change).

- 2026-06-27: open by Devana. Initial report written from static source inspection.

DEVANA-KEY: src/Mcp/Tools/RuntimeTools.php:373 | runtime-install-missing-ok-signal
DEVANA-SUMMARY: fixed | P2 | medium | kirby_runtime_install returns errors[] without ok:false, so partial install failures look like success to MCP clients that expect an ok flag.