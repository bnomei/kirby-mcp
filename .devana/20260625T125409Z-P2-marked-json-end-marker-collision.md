DEVANA-FINDING: v1
DEVANA-STATE: fixed | P2 | medium | security=no
DEVANA-KEY: src/Cli/McpMarkedJsonExtractor.php:25 | marked-json-end-marker-collision

# Runtime content tools break when content contains the JSON-END marker

## Finding

The Kirby runtime tools (`kirby_read_page_content`, `kirby_update_page_content`,
`kirby_read_site_content`/`kirby_update_site_content`, the file/user content
variants, and `kirby_run_cli_command`) frame their structured result by wrapping
a JSON document between two **plain, unescaped string delimiters**:

- `src/Mcp/Support/RuntimeCommand.php:41` (`emit`) prints
  `__KIRBY_MCP_JSON__` + newline, then `$cli->json($payload)`, then
  `__KIRBY_MCP_JSON_END__` + newline.
- The `$payload` contains the page/site/file/user **content field values
  verbatim** (e.g. `PageUpdate.php:184` / the read commands build
  `'content' => $content`).
- `src/Cli/McpMarkedJsonExtractor.php:19-30` extracts the block with
  `strpos($stdout, START)` then **the first** `strpos($stdout, END, $start)`,
  and `Json::decodeString` the slice between them.

Because the END marker is matched by raw `strpos` against stdout that already
contains the (unescaped-by-framing) content, a content value that contains the
literal string `__KIRBY_MCP_JSON_END__` makes `extract()` cut the JSON in the
middle of a string value. `Json::decodeString` then throws
(`Failed to parse JSON string`), `RuntimeCommandRunner::runMarkedJson`
(`src/Mcp/Support/RuntimeCommandRunner.php:62-67,86`) records a `parseError`, and
the tool returns `ok:false` even though the page/site/file is perfectly valid.

## Violated Invariant Or Contract

A framing delimiter must not collide with the payload it wraps. The extractor's
implicit contract is "the bytes between the START marker and the matching END
marker are exactly the JSON emitted by `emit()`." When the payload itself
contains the END marker, the matched END is not the emitter's terminator, so the
extracted slice is not the emitted JSON.

## Oracle

Producer/consumer contract between `RuntimeCommand::emit` (producer) and
`McpMarkedJsonExtractor::extract` (consumer), plus `JsonMarkers` (the shared
delimiter constants). The producer does not escape or encode the payload to keep
the delimiter unique; the consumer assumes the first END marker after START is
the terminator. The two disagree whenever content contains the delimiter.

## Counterexample

A page has a text/textarea/markdown field whose stored value contains the
substring `__KIRBY_MCP_JSON_END__` (for example a page documenting this tool, or
content planted by a Panel editor). stdout from the CLI subprocess is:

```
__KIRBY_MCP_JSON__
{"ok":true,"page":{...},"content":{"text":"... __KIRBY_MCP_JSON_END__ ..."}}
__KIRBY_MCP_JSON_END__
```

`extract()` returns `substr` up to the END marker **inside** the `text` value:
`{"ok":true,...,"content":{"text":"... ` — truncated, invalid JSON →
`Json::decodeString` throws → tool result is `ok:false` with
`parseError = "Failed to parse JSON string"`. The marker is plain ASCII
(`[A-Za-z_]`), so JSON string-encoding does not alter it; it survives verbatim
in stdout.

## Why It Might Matter

- Correctness/availability: any page/site/file/user whose content contains the
  END marker becomes unreadable and un-updatable through the MCP runtime content
  tools, with a misleading "unable to parse JSON output" error that points at the
  tooling rather than the content.
- Low-trust author leverage: a Panel content editor (lower privilege than the
  MCP/admin operator the tools serve) can plant the marker to make a page's
  content invisible to MCP-driven review/automation, or to break an
  agent-performed migration mid-run.
- It is also reachable accidentally by legitimate content (documentation, test
  fixtures) that mentions the marker.

Not a privilege/auth bypass and not data corruption (the slice cannot be forged
into a *different* valid JSON, because the real payload prefix is incomplete), so
this is P2 rather than higher.

## Proof

Contract mismatch + concrete counterexample value:

- Producer: `src/Mcp/Support/RuntimeCommand.php:41-46` emits
  `JsonMarkers::END` (`src/Mcp/Support/JsonMarkers.php:8` =
  `__KIRBY_MCP_JSON_END__`) right after a JSON payload that embeds content
  verbatim.
- Consumer: `src/Cli/McpMarkedJsonExtractor.php:25` —
  `$end = strpos($stdout, JsonMarkers::END, $start)` takes the **first**
  occurrence, then line 30 slices and `Json::decodeString` parses it.
- Failure handling that surfaces the bug as `ok:false`:
  `src/Mcp/Support/RuntimeCommandRunner.php:62-67` (catch → `parseError`) and
  `:86` (`payload` becomes null).
- Counterexample value: any content field equal to or containing
  `__KIRBY_MCP_JSON_END__`.

## Counterevidence Checked

- START-marker collision is NOT exploitable: `extract()` uses the *first* START
  (`strpos` from 0), which is always the emitter's real START, so content
  containing `__KIRBY_MCP_JSON__` is harmless. Only the END marker (first match
  after START) is vulnerable. Confirmed at `McpMarkedJsonExtractor.php:19,25`.
- JSON escaping does not save it: the marker is `[A-Za-z_]` only, so
  `json_encode` does not insert escapes inside it; it appears byte-for-byte in
  stdout.
- The failure is handled gracefully (no crash/DoS) — `RuntimeCommandRunner`
  catches the throw — but the content is silently lost from the result, which is
  the actionable defect.
- Markers are distinctive, so accidental collisions are rare; however the value
  is fully author/attacker-controllable and the result is deterministic, so it is
  weaponizable, not merely theoretical.
- Strongest reason it might be false: one could argue "no realistic content
  contains this marker." But content is untrusted relative to the tool operator,
  the marker is short and guessable (it is shipped in this repo's source and
  docs), and a single planted field deterministically denies the tool — which
  meets the bar for an actionable framing bug.

## Suggested Next Step

Make the framing collision-proof. Options (smallest first): base64-encode the
payload between the markers (so the delimiter can never appear in the framed
body), or have `extract()` anchor on markers that occupy their own lines
(`^__KIRBY_MCP_JSON_END__$`) and select the **last** END marker, or emit a
length prefix. Add a unit test with a content value containing
`__KIRBY_MCP_JSON_END__`.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2
`DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` prefix. Use one of: `open`,
`fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable
unless the same finding moved. Add dated notes below.

## Status Notes

- 2026-06-25: open by Devana. Static source inspection. Confirmed producer
  (`RuntimeCommand::emit`), consumer (`McpMarkedJsonExtractor::extract`),
  delimiter constants (`JsonMarkers`), and graceful-but-content-losing failure
  path (`RuntimeCommandRunner`). vendor/ not installed, so the exact encoder flags
  of `$cli->json()` were not inspected; the bug holds regardless because the
  truncated slice is structurally incomplete JSON.

- 2026-06-27: fixed. `McpMarkedJsonExtractor::extract()` no longer matches the *first* END marker via raw `strpos`. It now anchors on the *last line that is exactly* the END marker (`/^__KIRBY_MCP_JSON_END__\r?$/m`), with a `strrpos` (last-occurrence) fallback for inline framing. Because `CLI::json()` uses `JSON_PRETTY_PRINT` (newlines inside string values are escaped), a content-embedded marker is never alone on a physical line, so it can never be selected as the terminator. Selecting the last occurrence also fixes the single-line framing case. Added `tests/Unit/McpMarkedJsonExtractorTest.php` covering content that contains the END marker (both own-line and inline framing); existing `RuntimeCommandRunnerTest` cases still pass. phpstan clean.

DEVANA-KEY: src/Cli/McpMarkedJsonExtractor.php:25 | marked-json-end-marker-collision
DEVANA-SUMMARY: fixed | P2 | medium | Content containing the literal `__KIRBY_MCP_JSON_END__` collided with the unescaped result-framing marker, so the runtime content tools truncated their own JSON and returned ok:false for valid pages. Extractor now anchors on the last standalone-line END marker (with last-occurrence fallback), which content can never forge.
