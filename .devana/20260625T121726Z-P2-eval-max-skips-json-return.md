DEVANA-FINDING: v1
DEVANA-STATE: fixed | P2 | medium | security=no
DEVANA-KEY: src/Mcp/Commands/EvalPhp.php:167 | eval-max-skips-json-return

# `EvalPhp` `--max` does not bound JSON-serializable return values

## Finding

`EvalPhp` applies `--max` truncation to captured stdout and to `var_export` fallback dumps, but not to return values that successfully `json_encode`. Large structured return values can bypass the configured size limit and flow into the MCP JSON payload unchanged.

## Violated Invariant Or Contract

The `--max` argument is documented as the maximum characters for captured stdout/return dump output, with default 20000. Operators enabling eval expect bounded output per call.

## Oracle

- `EvalPhp::definition()` `--max` description (lines 36–39).
- Truncation guards exist for stdout (lines 160–163) and dump fallback (lines 191–195).
- JSON return path decodes the full encoded value without a length check (lines 167–180).

## Counterexample

With eval enabled and `confirm=true`, run:

```php
return $kirby->option('email');
```

or `return $site->index()->toArray();`

If the value JSON-encodes successfully, `return.json` contains the full structure even when `--max=20000`, while an equivalent value that fails JSON encoding would be truncated in `return.dump`.

## Why It Might Matter

Eval is disabled by default but intentionally dangerous when enabled. Unbounded JSON returns can leak large config blobs, content snapshots, or secrets into agent logs/context despite an explicit max setting.

## Proof

Dataflow trace:

```
eval($code) → $resultValue
  → json_encode($resultValue) succeeds
  → $resultJson = json_decode($encoded)   // no maxChars check
  → emit payload with full return.json
```

Stdout/dump paths apply `maxChars`; JSON path does not.

## Counterevidence Checked

- Eval requires explicit enablement and `--confirm`.
- Non-JSON-serializable values fall back to truncated `var_export`.
- Counterevidence does not cap JSON-serializable returns, which are the common case for structured Kirby data.

## Suggested Next Step

Apply the same `maxChars` truncation to encoded JSON strings or summarized return values before emitting the payload.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-25: open by Devana. Initial report written from static source inspection.
- 2026-06-27: fixed. `EvalPhp::run()` now length-checks the encoded JSON before emitting it: when `strlen($encoded) > maxChars`, the full structure is no longer decoded into `return.json`; instead the truncated encoded string is emitted via the existing `return.dump` channel with `dumpTruncated=true`. The `var_export` fallback guard was tightened to `resultJson === null && resultDump === null` so it does not overwrite the bounded JSON dump. Updated the existing `max=3` runtime-command test (it had codified the unbounded behavior) and added `it('keeps small JSON eval returns intact but bounds oversized ones by --max')`. phpstan clean.

DEVANA-KEY: src/Mcp/Commands/EvalPhp.php:167 | eval-max-skips-json-return
DEVANA-SUMMARY: fixed | P2 | medium | EvalPhp truncates stdout and dump output but not JSON-serializable return values, so --max can be bypassed for large structured results.