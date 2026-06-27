DEVANA-FINDING: v1
DEVANA-STATE: open | P3 | high | security=no
DEVANA-KEY: src/Support/FuzzySearch.php:51 | fuzzysearch-levenshtein-false-positive

# FuzzySearch matches any haystack word longer than 255 bytes (levenshtein -1 sentinel)

## Finding

`FuzzySearch::fuzzyLevenshtein()` returns `$dist <= $maxDist`
(`FuzzySearch.php:51-53`). PHP's `levenshtein()` returns `-1` when either
argument exceeds 255 bytes (a hardcoded limit). Since `$maxDist` is `>= 0`
(default 2, and callers clamp to `max(0, ...)`), `-1 <= $maxDist` is always
true, so any candidate word ≥ 256 bytes is reported as a fuzzy match for an
arbitrary needle.

In `fuzzySearch()` the first such word short-circuits the loop to `return true`
(`FuzzySearch.php:40-43`), so the whole document "matches" any query.

## Violated Invariant Or Contract

`fuzzyLevenshtein` must return `true` only when the edit distance is within
`$maxDist`. The `-1` error sentinel must not be treated as "within distance."

## Oracle

PHP `levenshtein()` documented behavior: returns `-1` if one of the arguments
exceeds 255 characters. There is no `if ($dist < 0)` handling and no length
guard before the call, so the sentinel leaks into the `<=` comparison.

## Counterexample

Needle `"zzz"`; haystack containing one 300-character alphanumeric token (e.g. a
base64 blob or long hash with no whitespace, common in markdown KB docs). After
tokenization (`FuzzySearch.php:30-32` splits on non-alphanumerics), the long
token remains a single word. `levenshtein("zzz", <300-char token>)` returns `-1`
→ `-1 <= 2` → `true`. The document is returned for a query it has nothing to do
with (e.g. via `KbTools` KB search, which uses `fuzzySearch`).

## Why It Might Matter

KB / docs / tool search returns false-positive results — irrelevant documents
surface for unrelated queries whenever the document contains a long unbroken
token. Correctness/relevance defect in search output. P3 because it requires a
≥256-byte unbroken token in the corpus.

## Proof

Counterexample value + control-flow: `FuzzySearch.php:34-53` (no `< 0` guard,
`$maxDist >= 0`) plus documented `levenshtein` 255-char limit returning `-1`.

## Counterevidence Checked

- The regex at line 30 splits on `[^\p{L}\p{N}]`, so URLs/paths with `/`, `.`,
  `-` are broken up; the trigger needs a genuinely long alphanumeric token
  (base64 image data, hashes, minified inline content) — plausible but not in
  every corpus.
- No length check before `levenshtein`; `$maxDist` cannot be negative
  (`max(0, min(10, ...))` in `KbTools`), so the `-1` sentinel is never filtered.
- The earlier `str_contains` check (line 39) does not pre-empt the case (short
  needle not contained in the long, different token).
- Strongest false-positive reason: needs a specific corpus shape — but the
  defect (treating `-1` as a match) is unconditional once such a token exists.

## Suggested Next Step

Guard the sentinel: `if ($dist < 0) { return false; }` (or compare lengths /
use `mb_strlen` bounds before calling `levenshtein`).

## Agent Handoff

Preserve the finding body; update line 2 and the `DEVANA-SUMMARY:` prefix.

## Status Notes

- 2026-06-27: open by Devana. Verified `FuzzySearch.php:49-53` lacks a `< 0`
  guard and callers keep `$maxDist >= 0`.

DEVANA-KEY: src/Support/FuzzySearch.php:51 | fuzzysearch-levenshtein-false-positive
DEVANA-SUMMARY: open | P3 | high | levenshtein() returns -1 for words over 255 bytes and the `-1 <= maxDist` test treats it as a match, so any document containing a long unbroken token fuzzy-matches arbitrary queries.
