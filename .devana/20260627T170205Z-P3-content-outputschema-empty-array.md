DEVANA-FINDING: v1
DEVANA-STATE: open | P3 | medium | security=no
DEVANA-KEY: src/Mcp/Tools/RuntimeTools.php:81 | content-outputschema-empty-array

# Read-content tools advertise `content` as object but emit `[]` for empty content

## Finding

The output schema for the read-content tools declares `content` as a required
JSON object (`RuntimeTools.php:81-84` `type => 'object'`, plus `content` in the
`required` list at `:97`; identical sub-schemas for the page/site/user variants).
The producing commands set `content => $model->content($lang)->toArray()`
(`FileContent.php:81`, also `PageContent.php`, `SiteContent.php`,
`UserContent.php`). When the model has no stored content fields, `toArray()`
returns `[]` — a PHP empty array that JSON-encodes to `[]` (array), not `{}`
(object). So `structuredContent.content` is an array where the declared schema
says object.

## Violated Invariant Or Contract

A value advertised under an `#[McpTool(outputSchema: ...)]` property must
validate against that property's declared type. `content` is declared
`type: object` and `required`, but is emitted as `[]` for fields-less models.

## Oracle

The `outputSchema` itself (`RuntimeTools.php:81-84,97`) is the contract;
`$model->content()->toArray() === []` for a model with no content is the
producing fact (PHP empty arrays serialize as JSON arrays).

## Counterexample

Call `kirby_read_file_content` on an uploaded media file (e.g. an image) that has
no `.txt` sidecar and no blueprint-default content. `$file->content()->toArray()`
is `[]` → emitted `"content": []` → fails the declared `content: {type:"object"}`
(which is also `required`). Smallest state: one image file, zero meta fields.

## Why It Might Matter

For strict clients (this repo targets Codex-style strict MCP clients) that
validate `structuredContent` against `outputSchema`, a routine read on a
content-less file/page/site/user returns a payload that violates the advertised
schema — a contract/conformance defect that can surface as a client-side
validation error on otherwise-valid data. P3: impact depends on whether the
client/SDK enforces output-schema validation (unverified — vendor SDK absent).

## Proof

Schema/runtime contract mismatch: declared `content: {type:"object"}` + required
(`RuntimeTools.php:81-84,97`) vs produced `[]` (`FileContent.php:81` →
`RuntimeCommandResult` → `maybeStructuredResult` re-encode, no `JSON_FORCE_OBJECT`).

## Counterevidence Checked

- Confirmed the JSON round-trip preserves `[]` as a JSON array (CLI emit →
  `json_decode` → re-`json_encode`, no `JSON_FORCE_OBJECT` anywhere).
- `keys`/`truncatedKeys`/`fieldSchemas` are declared `array` and are genuine
  lists, so only `content` (typed `object`) is affected.
- Strongest false-positive reason: runtime impact requires the client/SDK to
  validate `structuredContent` against `outputSchema`; the `mcp/sdk` package is
  not installed here, so enforcement could not be confirmed. Even without
  thrown errors, the contract is mis-advertised to strict clients.

## Suggested Next Step

Either cast the empty case to an object at emit time (e.g. force `{}` /
`(object)` when `content === []`) or relax the schema to
`type: ['object','array']` for `content`. Apply consistently to the page/site/
user content tools.

## Agent Handoff

Preserve the finding body; update line 2 and the `DEVANA-SUMMARY:` prefix.

## Status Notes

- 2026-06-27: open by Devana. Verified schema at `RuntimeTools.php:81-84,97` and
  producer at `FileContent.php:81`; `[]` survives the encode round-trip. SDK
  output-schema enforcement unverified (vendor absent).

DEVANA-KEY: src/Mcp/Tools/RuntimeTools.php:81 | content-outputschema-empty-array
DEVANA-SUMMARY: open | P3 | medium | Read-content tools declare `content` as a required object but emit `[]` (JSON array) for models with no content fields, violating the advertised outputSchema for strict clients.
