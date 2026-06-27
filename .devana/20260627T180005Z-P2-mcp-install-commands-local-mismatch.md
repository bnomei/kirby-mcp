DEVANA-FINDING: v1
DEVANA-STATE: open | P2 | high | security=no
DEVANA-KEY: src/Mcp/Commands/Install.php:45 | mcp-install-commands-local-mismatch

# `kirby mcp:install` targets `commands` root while MCP stack prefers `commands.local`

## Finding

The Kirby CLI wrappers `mcp:install` and `mcp:update` pass `commandsRootOverride: $kirby->root('commands')` to `RuntimeCommandsInstaller`. The rest of the MCP stack (`KirbyRoots::commandsRoot()`, `kirby_runtime_install`, `kirby_runtime_status`, `RuntimeCommandRunner`) prefers `commands.local` when present. Install can succeed under the wrong tree while runtime status reports missing commands.

## Violated Invariant Or Contract

Runtime command templates must live in the same commands root the MCP server probes and executes. `RuntimeTools` documentation states install writes to "site/commands or commands.local".

## Oracle

- `KirbyRoots::commandsRoot()` line 23: `commands.local ?? commands`.
- `Install::run()` lines 45–55: override uses `$kirby->root('commands')` only.
- `Update::run()` lines 37–52: same override.
- `RuntimeTools::runtimeInstall()` uses installer without override (inspector default).
- `tests/Unit/KirbyRootsTest.php` shows `commands.local` as canonical commands root.

## Counterexample

Project configures `commands.local` (Kirby roots output includes `["commands.local"] => "/app/custom/commands"`).

Operator runs `kirby mcp:install` (or agent uses the thin `commands/mcp/install` proxy).

- Files written to `/app/site/commands/mcp/...` (or `root('commands')` path).
- `kirby_runtime_status` checks `/app/custom/commands/mcp/...` → `installed: false`, `needsRuntimeInstall: true`.
- Runtime tools (`kirby_render_page`, content reads) fail until `kirby_runtime_install` is used instead.

## Why It Might Matter

Operators following Kirby CLI docs or installed proxy commands believe runtime is ready after `mcp:install`. Custom `commands.local` setups silently break runtime-backed MCP tools.

## Proof

**Cross-entry mismatch:** `kirby mcp:install` vs `kirby_runtime_install` resolve different `commandsRoot` values on the same project.

**Control-flow:** `Install::run` → `commandsRootOverride = root('commands')` → installer writes → MCP status/runner reads `commands.local` first → empty/wrong tree.

## Counterevidence Checked

- Default projects without `commands.local` use the same `commands` path; no divergence.
- `bin/kirby-mcp install` uses inspector default (no override); only the Kirby CLI `mcp:*` entry points are affected.
- Install still copies files somewhere useful on plain starterkit layouts; bug appears when `commands.local` is active.

## Suggested Next Step

Align `Install`/`Update` root resolution with `KirbyRoots::commandsRoot()` (via `KirbyRootsInspector`) instead of `root('commands')` alone.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-27: open by Devana. Initial report written from static source inspection.

DEVANA-KEY: src/Mcp/Commands/Install.php:45 | mcp-install-commands-local-mismatch
DEVANA-SUMMARY: open | P2 | high | kirby mcp:install/update write to root(commands) but MCP runtime checks commands.local first, causing perpetual needsRuntimeInstall on custom roots.