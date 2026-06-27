DEVANA-FINDING: v1
DEVANA-STATE: fixed | P2 | medium | security=no
DEVANA-KEY: src/Mcp/Commands/QueryDot.php:205 | query-dot-model-extension-collision

# Query model resolver treats dotted page ids as files

## Finding

`QueryDot::resolveModel()` resolves any model argument whose basename has a file extension via `$kirby->file()` before trying `$kirby->page()`. Page ids or slugs that contain a dot therefore follow the file code path, which can return the wrong model or fail with "Model not found" even when the page exists.

## Violated Invariant Or Contract

`QueryDot` docs and `kirby_query_dot` tool text say the `model` argument is treated as a page id unless it is a UUID, email, explicit file path with extension, or `site`. `PageResolver` and `kirby_read_page_content` resolve the same identifier as a page first.

## Oracle

- `QueryDot::definition()` model arg description (lines 47–48).
- `PageResolver::resolve()` tries `$kirby->page()` before UUID fallback (`PageResolver.php` lines 23–29).
- `tests/Integration/KirbyQueryDotToolTest.php` covers `model: "notes"` but not dotted page ids.

## Counterexample

Project has page id `release-2.0` and no matching file id.

- `kirby_read_page_content(id="release-2.0")` resolves the page via `PageResolver`.
- `kirby_query_dot(query="title", model="release-2.0", confirm=true)` calls `looksLikeFile()` (extension `0`), then `$kirby->file("release-2.0")`, never `$kirby->page()`, and returns `Model not found for: release-2.0`.

## Why It Might Matter

Query results and context summaries become wrong or unavailable for legitimate page ids, breaking agent workflows that reuse page ids from read/update tools.

## Proof

Control-flow trace with counterexample value:

```
QueryDot::resolveModel("release-2.0")
  → Uuid::is → false
  → looksLikeEmail → false
  → looksLikeFile → true (PATHINFO_EXTENSION = "0")
  → return $kirby->file("release-2.0")   // skips $kirby->page()
```

## Counterevidence Checked

- Extension heuristic is documented for real file paths like `blog/post/cover.jpg`.
- UUIDs and `site` still resolve correctly.
- Email-shaped values intentionally use the user branch first.
- Counterevidence does not cover dotted page slugs, which are common in CMS content trees.

## Suggested Next Step

Try `$kirby->page()` before the extension heuristic, or require a parent path segment (e.g. `page/file.jpg`) before treating an argument as a file id.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-25: open by Devana. Initial report written from static source inspection.
- 2026-06-27: fixed. `QueryDot::resolveModel()` now tries `$kirby->page()` before the `looksLikeFile()` extension heuristic, so dotted page slugs (e.g. `release-2.0`) resolve as pages, matching `PageResolver` and the read tools. Falls back to `$kirby->file()` only when no page matches. Added unit test `QueryDotResolveModelTest` (reflection on the private resolver) covering the dotted-page case and the genuine file fallback. phpstan clean.

DEVANA-KEY: src/Mcp/Commands/QueryDot.php:205 | query-dot-model-extension-collision
DEVANA-SUMMARY: fixed | P2 | medium | QueryDot treats any dotted basename as a file id, so page slugs like release-2.0 fail or resolve to the wrong model.