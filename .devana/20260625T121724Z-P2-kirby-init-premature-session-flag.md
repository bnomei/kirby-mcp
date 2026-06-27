DEVANA-FINDING: v1
DEVANA-STATE: fixed | P2 | medium | security=no
DEVANA-KEY: src/Mcp/Tools/SessionTools.php:68 | kirby-init-premature-session-flag

# `kirby_init` marks the session initialized before validation succeeds

## Finding

`SessionTools::init()` calls `SessionState::markInitCalled()` at the start of the method, before composer validation and other init work. If init later throws `ToolCallException`, the session still records init as complete, so `RequireInitForToolsHandler` allows subsequent tool calls even though init failed.

## Violated Invariant Or Contract

Init gating is meant to ensure the client completed a successful `kirby_init` before using other tools. A failed init should not unlock the rest of the tool surface.

## Oracle

- `src/Mcp/AGENTS.md`: all tool calls except `kirby_init` are init-guarded.
- `RequireInitForToolsHandler` only checks `SessionState::initCalled()`, not init success.
- `SessionState::reset()` exists but is not invoked on init failure.

## Counterexample

1. MCP session calls `kirby_init` against a directory whose `composer.json` lacks `getkirby/cms`.
2. `markInitCalled()` runs, then line 79 throws `ToolCallException`.
3. Client receives an init error, but the persisted session flag remains true.
4. Next call to `kirby_blueprints_index` passes the init guard and executes against the same invalid project context.

## Why It Might Matter

Clients that treat a failed init as a hard stop can still drive tools afterward, producing confusing partial failures or actions against the wrong project root.

## Proof

State transition mismatch:

```
CallTool(kirby_init)
  → SessionState::markInitCalled()   // init=true
  → ComposerInspector check fails
  → ToolCallException
  → session saved with init=true

CallTool(kirby_blueprints_index)
  → RequireInitForToolsHandler sees initCalled() === true
  → tool executes
```

## Counterevidence Checked

- If `$session` is null (no session), `markInitCalled()` is a no-op and the guard still blocks.
- Global-reference init path can succeed after the early mark.
- Counterevidence does not roll back the flag on project-mode validation failure.

## Suggested Next Step

Move `markInitCalled()` to the successful return path, or call `SessionState::reset()` in a `catch`/`finally` when init fails.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-25: open by Devana. Initial report written from static source inspection.
- 2026-06-27: fixed. Moved `SessionState::markInitCalled()` out of the method entry and onto the two successful return paths (global-reference branch and the project-mode return). A failed `kirby_init` (e.g. project without `getkirby/cms`) now throws without marking the session initialized, so `RequireInitForToolsHandler` keeps blocking other tools. Added unit test `it('does not mark the session initialized when project init fails validation')`. phpstan clean.

DEVANA-KEY: src/Mcp/Tools/SessionTools.php:68 | kirby-init-premature-session-flag
DEVANA-SUMMARY: fixed | P2 | medium | kirby_init sets the session init flag before validation, so a failed init still unlocks other MCP tools in the same session.