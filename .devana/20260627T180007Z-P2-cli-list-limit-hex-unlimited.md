DEVANA-FINDING: v1
DEVANA-STATE: fixed | P2 | medium | security=no
DEVANA-KEY: src/Mcp/Commands/Collections.php:76 | cli-list-limit-hex-unlimited

# Runtime list commands treat hex numeric limits as zero (unlimited pagination)

## Finding

Multiple runtime index commands parse `--limit` with `is_numeric()` followed by `(int)` cast. PHP accepts hexadecimal strings as numeric (`"0x10"` → `(int)` 0). Documented semantics treat `limit=0` as no cap. A caller passing a hex limit string accidentally or intentionally receives an unbounded result set.

## Violated Invariant Or Contract

`--limit` is documented as an integer pagination cap ("0 means no limit"). Values that look like integers but use hex notation should either parse as their numeric value or be rejected—not silently become unlimited.

## Oracle

- `Collections::definition()` line 48: "Pagination limit (0 means no limit). Default: 0."
- `Collections::run()` lines 75–78: `$limit = is_numeric($limitRaw) ? (int) $limitRaw : 0`.
- Same pattern in `Controllers`, `Routes`, `Snippets`, `Models`, `Templates`, `Plugins`, `Blueprints` commands.

## Counterexample

`kirby collections --limit=0x10` (or MCP runtime tool forwarding that argument).

- `is_numeric("0x10")` → true
- `(int) "0x10"` → 0
- Pagination branch treats `limit === 0` as no slice → returns entire filtered collection list in one JSON payload.

Caller may have intended 16 items (0x10) or any bounded page; instead receives full index (memory/response blowup on large projects).

## Why It Might Matter

Large Kirby projects can produce very large structured index responses through MCP runtime tools, affecting agent context size and server memory. Scientific notation (`1e2`) truncates similarly (`(int)"1e2"` = 1).

## Proof

**Counterexample value:** `limitRaw = "0x10"` → stored `limit = 0` → no `array_slice` cap.

**Contract mismatch:** CLI arg described as pagination limit; PHP casting rules reinterpret hex as zero/unlimited.

## Counterevidence Checked

- Plain decimal strings work as expected (`"50"` → 50).
- Negative limits clamp to 0 (unlimited), which is consistent but separate.
- Tool-layer limits (e.g. `kirby_kb_search` max 50) use `max/min` on integers after cast; runtime CLI commands do not.
- Impact requires reaching runtime commands (install + RUNTIME scope on HTTP).

## Suggested Next Step

Parse limits with `filter_var($raw, FILTER_VALIDATE_INT)` or reject non-decimal numeric strings; treat invalid input as an explicit error instead of unlimited.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-27: fixed. All eight runtime index commands (`Collections`, `Controllers`, `Routes`, `Snippets`, `Models`, `Templates`, `Plugins`, `Blueprints`) parsed `--cursor`/`--limit` with `is_numeric($raw) ? (int) $raw : 0`, which collapses present-but-non-decimal values to `0` (= "no limit"): `0x10` is rejected by `is_numeric()` in PHP 8 (→ 0) and `1e2` casts via `(int)` to `1`, so a caller intending a bounded page silently receives the entire index (memory/context blowup on large projects). Added `RuntimeCommand::parsePaginationArg()` (accepts only `^[+-]?\d+$`, returns the int, `$default` for absent/empty, `false` for present-but-invalid) and `RuntimeCommand::paginationArgOrEmitError()` (resolves a non-negative value, clamps negatives to 0, or emits `ok=false` with `--<arg> must be a base-10 integer.` and returns null). Replaced the unsafe block in all eight commands with these helpers — they now fail closed on a malformed limit/cursor instead of returning an unbounded result set. Valid decimal strings and ints (the test harness passes ints) still parse correctly; absent args still default to 0. Added integration test `it rejects non-decimal pagination limits instead of disabling pagination` (`0x10`,`1e2`,`10.5`,`sixteen`,bad cursor `0x1` → error; `"1"` → limit 1). Existing list/pagination tests (19) still pass; phpstan clean.

- 2026-06-27: open by Devana. Initial report written from static source inspection.

DEVANA-KEY: src/Mcp/Commands/Collections.php:76 | cli-list-limit-hex-unlimited
DEVANA-SUMMARY: fixed | P2 | medium | is_numeric plus int cast treats hex limit strings like 0x10 as zero, disabling pagination on runtime index CLI commands.