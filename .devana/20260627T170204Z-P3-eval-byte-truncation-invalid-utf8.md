DEVANA-FINDING: v1
DEVANA-STATE: open | P3 | medium | security=no
DEVANA-KEY: src/Mcp/Commands/EvalPhp.php:161 | eval-byte-truncation-invalid-utf8

# EvalPhp byte-truncates stdout/dump, can emit invalid UTF-8 into the MCP JSON sink

## Finding

`EvalPhp` truncates captured `stdout` and the var_export `dump` with byte-based
`strlen`/`substr` (`EvalPhp.php:160-163` and `:191-194`), even though the `--max`
argument is documented as "Max **chars** for captured stdout/return dump"
(`EvalPhp.php:38`). Byte truncation can cut in the middle of a multibyte UTF-8
character, leaving an invalid byte sequence in `$stdout`/`$resultDump`.

The truncated value is placed into `$payload['stdout']` (and `resultDump`) and
emitted as MCP-marked JSON via `RuntimeCommand::emit()` →
`echo $cli->json($payload)` (`RuntimeCommand.php:41-46`). The repo's own
`DumpValueNormalizer` deliberately uses `mb_strlen`/`mb_substr`
(`DumpValueNormalizer.php:37-38`) for exactly this reason, establishing the
intended char-safe truncation contract that `EvalPhp` violates.

This is distinct from the separate finding `EvalPhp.php:167 max-skips-json`
(which is about JSON-serializable return values bypassing `--max`).

## Violated Invariant Or Contract

`--max` is documented in chars and the codebase's own normalizer truncates by
characters with `mb_substr`. Truncation must not split a multibyte character and
must not produce invalid UTF-8 in a value destined for JSON output.

## Oracle

`DumpValueNormalizer.php:37-38` (`mb_substr`) is the in-repo oracle for the
intended behavior. PHP `substr` operates on bytes; cutting mid-codepoint yields
invalid UTF-8.

## Counterexample

`kirby_eval` (confirmed) with `--max 3` and code `echo "aaé";` (`é` = bytes
`C3 A9`): `strlen("aaé") = 4 > 3` → `substr($s, 0, 3) = "aa\xC3"` (lone lead
byte → invalid UTF-8). With default `--max 20000`, any eval echoing >20000 bytes
of UTF-8 whose 20000th byte falls inside a multibyte character reproduces it.

## Why It Might Matter

An invalid-UTF-8 `stdout`/`resultDump` is fed to the JSON encoder used by
`emit()`. Depending on the encoder's flags, this either substitutes a
replacement character (mild mojibake at the truncation point) or, if the encoder
lacks `JSON_INVALID_UTF8_SUBSTITUTE`/`JSON_THROW_ON_ERROR`, returns `false` —
in which case `echo false` writes an empty payload between the
`__KIRBY_MCP_JSON__` markers and the entire eval result is lost (parse error /
`ok:false`). Eval is opt-in + confirm-gated, so P3.

## Proof

Control-flow + counterexample value: `EvalPhp.php:160-163` (byte `substr`) →
`$payload['stdout']` → `RuntimeCommand::emit` (`RuntimeCommand.php:44`) →
`$cli->json($payload)` → MCP-marked output. Contrast with
`DumpValueNormalizer.php:37-38` (`mb_substr`).

## Counterevidence Checked

- Eval is disabled by default and requires `--confirm`, narrowing exposure.
- The catastrophic "empty envelope" outcome depends on the `getkirby/cli`
  `CLI::json()` encoder flags (whether it sets `JSON_INVALID_UTF8_SUBSTITUTE`).
  Vendor is not installed in this checkout, so that downstream consequence could
  not be confirmed here; the **source-certain** defect is the byte-vs-char
  truncation that can emit invalid UTF-8, inconsistent with the repo's own
  `mb_substr` normalizer.
- Strongest false-positive reason: if the emit encoder substitutes invalid
  UTF-8, impact is only a replacement char at the cut — still a contract
  deviation from the documented "chars" semantics, but minor.

## Suggested Next Step

Use `mb_strlen`/`mb_substr` for the `--max` truncation in `EvalPhp` (matching
`DumpValueNormalizer`). Optionally confirm the emit encoder sets
`JSON_INVALID_UTF8_SUBSTITUTE` so malformed bytes never collapse the envelope.

## Agent Handoff

Preserve the finding body; update line 2 and the `DEVANA-SUMMARY:` prefix.

## Status Notes

- 2026-06-27: open by Devana. Verified byte `substr` at `EvalPhp.php:160-163`,
  `:191-194`; `mb_substr` intent in `DumpValueNormalizer.php:37-38`. Emit
  encoder flags unverified (vendor absent).

DEVANA-KEY: src/Mcp/Commands/EvalPhp.php:161 | eval-byte-truncation-invalid-utf8
DEVANA-SUMMARY: open | P3 | medium | EvalPhp truncates stdout/dump by bytes (substr) despite a documented "chars" limit, splitting multibyte UTF-8 and emitting invalid bytes into the MCP JSON output (repo's own normalizer uses mb_substr).
