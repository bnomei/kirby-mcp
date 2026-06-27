DEVANA-FINDING: v1
DEVANA-STATE: open | P2 | medium | security=yes
DEVANA-KEY: src/Mcp/Tools/DumpTools.php:51 | dump-log-cross-session-read

# `kirby_dump_log_tail` reads project-wide dump log without session isolation

## Finding

`mcp_dump()` output is appended to a single project file `.kirby-mcp/dumps.jsonl`. `kirby_dump_log_tail` reads that file by project root; `traceId` is optional and `limit=0` returns all events. Any MCP session with `RUNTIME` scope (or stdio access) can read debug output from other clients' render runs on the same project.

## Violated Invariant Or Contract

Dump trace IDs are documented as session-scoped (`DumpState::lastTraceId($session)`), implying tail access is tied to the active client's render context. The log sink is shared and readable across sessions.

## Oracle

- `DumpTools::dumpLogTail()` description: "`limit=0` returns all."
- `DumpLogReader::tail()` filters by optional `traceId` and `path`; omitting both reads the full log (subject to limit).
- `DumpLogWriter::filePath()` is `{projectRoot}/.kirby-mcp/dumps.jsonl` (no session dimension).
- `HttpScopePolicy` classifies `kirby_dump_log_tail` as `RUNTIME`, not per-session.

## Counterexample

1. Session A calls `kirby_render_page` → `mcp_dump()` captures request data → events written with `traceId=T1`.
2. Session B (different HTTP MCP session or stdio client) calls `kirby_dump_log_tail(limit=0)` without `traceId`.
3. Response includes Session A's events (potentially PII, tokens, config snippets depending on template code).

## Why It Might Matter

Shared hosting of MCP HTTP on one Kirby project, or multiple agents on stdio against the same project, can leak debug material across trust boundaries. `SecretMasker` reduces secret leakage on write but does not scope reads.

## Proof

**Dataflow:** Session A render → `DumpLogWriter::append(projectRoot)` → shared JSONL → Session B `DumpLogReader::tail(projectRoot, traceId: null, limit: 0)` → all events returned.

**Trust label lost:** `traceId` is session-derived for default filter, but caller can omit it or supply another session's id if known.

## Counterevidence Checked

- Passing the caller's own `traceId` (from their last render) limits exposure to their events; tool does not enforce that when `traceId` is explicitly null/omitted with `limit=0`.
- `SecretMasker` masks some values at write time; gaps exist (see `dump-secret-masker-key-name-gap`).
- Dump feature is debug-oriented; risk depends on what templates dump.

## Suggested Next Step

Default `kirby_dump_log_tail` to the caller's session `lastTraceId` when available and reject `limit=0` without `traceId` on HTTP transport, or partition logs by session id in the file path.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-27: open by Devana. Initial report written from static source inspection.

DEVANA-KEY: src/Mcp/Tools/DumpTools.php:51 | dump-log-cross-session-read
DEVANA-SUMMARY: open | P2 | medium | kirby_dump_log_tail reads the shared project dumps.jsonl and limit=0 without traceId returns debug events from other MCP sessions.