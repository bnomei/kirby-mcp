DEVANA-FINDING: v1
DEVANA-STATE: open | P1 | medium | security=yes
DEVANA-KEY: src/Dumps/SecretMasker.php:126 | dump-secret-masker-key-name-gap

# SecretMasker leaks secret values stored under sensitive keys in structured dumps

## Finding

`SecretMasker::maskRecursive()` only redacts secrets that match a value-format
regex. For structured data (arrays/objects), it walks each entry and runs
`mask()` on the key and independently on the value. A secret whose *value* does
not match any vendor token pattern is written through verbatim, even when it
sits under an obviously sensitive key like `password`, `secret`, `token`, or
`api_key`.

The masker's own `Password Field` pattern (`SecretMasker.php:53`) declares the
intent to redact `password|passwd|pwd|secret|token|api_key|apikey|auth`
assignments, but that regex only matches an *inline serialized* string of the
form `key: "value"` / `key="value"`. The normal dump pipeline normalizes data
into a structured PHP array before masking (`DumpLogWriter::append` →
`maskRecursive`, `DumpLogWriter.php:76-77`), so the inline-string pattern never
fires and there is no key-name-aware redaction of the value.

## Violated Invariant Or Contract

`src/Cli/AGENTS.md` / `src/Mcp/AGENTS.md` intent: secrets are masked before dump
output is exposed via MCP tools/resources. A value dumped under a sensitive key
(`password`, `secret`, `token`, ...) must not reach the persisted dump log or
the `kirby_dump_log_tail` MCP output in plaintext.

## Oracle

The presence of the `Password Field` default pattern proves the authors intend
to redact values assigned to sensitive keys. The recursive masker is the sole
redaction layer; `DumpLogReader::tail` returns stored events verbatim
(`src/Mcp/Tools/DumpTools.php`), so whatever survives masking is exposed.

## Counterexample

In a Kirby controller/template a developer calls:

```php
mcp_dump(kirby()->option('db'));   // ['user' => 'root', 'password' => 'S3cretDbPass!']
mcp_dump($_ENV);                   // ['APP_SECRET' => 'plain-non-token-value', ...]
```

`DumpValueNormalizer` keeps this a structured array (key + scalar). In
`maskRecursive` (`SecretMasker.php:138-141`) the key `"password"` matches no
regex and the value `"S3cretDbPass!"` matches none of the format patterns (it
is not `sk-…`, not a JWT, not AWS, etc.), and the `Password Field` regex cannot
match because there is no inline `key:"value"` literal — only an array pair.
Plaintext `S3cretDbPass!` is written to `.kirby-mcp/dumps.jsonl` and returned by
`kirby_dump_log_tail`.

## Why It Might Matter

DB passwords, app secrets, and free-form API credentials that do not match a
known token shape are persisted to disk and surfaced over the MCP transport to
any client able to call `kirby_dump_log_tail`. The dump feature exists precisely
to inspect runtime values, so dumping config/env is an expected use; the masker
is the safety net and it silently fails for the most common secret-storage
shape (a value under a named key).

## Proof

Dataflow trace: secret value under sensitive key (config/env) → `mcp_dump()` →
`DumpValueNormalizer` (structured array preserved) → `DumpLogWriter::append`
(`DumpLogWriter.php:76-77`) → `SecretMasker::maskRecursive` (`SecretMasker.php:126-147`,
value masked only by format regex; no key-name rule) → `.kirby-mcp/dumps.jsonl`
→ `DumpLogReader::tail` → `kirby_dump_log_tail` MCP result.

## Counterevidence Checked

- `maskRecursive` does mask string keys and string values, and recurses — but
  the value-side masking is purely format-based, so a non-format-matching value
  passes through (`SecretMasker.php:132-146`).
- Existing tests pass only because their secret *values* independently match a
  vendor regex (`sk-…`, `ghp_…`) or use the inline-string form; no test covers
  `['password' => 'plainvalue']` (`tests/Unit/SecretMaskerTest.php`).
- Dumps are opt-in (`KIRBY_MCP_DUMPS_ENABLED` / config), which bounds exposure,
  but does not prevent it once enabled — and the masker is advertised as the
  protection that makes dumps safe.
- Strongest false-positive reason: "a content regex masker inherently cannot
  redact arbitrary-format values." True in general, but the `Password Field`
  pattern shows specific intent to catch sensitive *keys*; the gap is that this
  intent is implemented only for the serialized-string shape and not the
  default structured-array path, which is a genuine inconsistency, not a
  declared limitation.

## Suggested Next Step

Add key-name-aware redaction to `maskRecursive`: when a string key matches the
sensitive-key list (`password|passwd|pwd|secret|token|api_key|apikey|auth`),
redact the associated scalar value regardless of its format. Add a unit test
for `['password' => 'plainvalue']`.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2
`DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` prefix.

## Status Notes

- 2026-06-27: open by Devana. Verified statically: `maskRecursive` has no
  key-name rule; `Password Field` regex matches only inline `key:"value"`;
  `DumpLogWriter.append` applies `maskRecursive` to normalized structured data.

DEVANA-KEY: src/Dumps/SecretMasker.php:126 | dump-secret-masker-key-name-gap
DEVANA-SUMMARY: open | P1 | medium | SecretMasker redacts only format-matching values, so a plaintext secret stored under a sensitive key (e.g. password) flows unmasked into the dump log and kirby_dump_log_tail output.
