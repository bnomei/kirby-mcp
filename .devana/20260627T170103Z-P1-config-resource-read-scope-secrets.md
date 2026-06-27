DEVANA-FINDING: v1
DEVANA-STATE: open | P1 | high | security=yes
DEVANA-KEY: src/Mcp/Resources/ConfigResources.php:44 | config-resource-read-scope-secrets

# `kirby://config/{option}` exposes secrets at read scope

## Finding

The `kirby://config/{option}` resource template is classified as a read-scoped HTTP operation and returns raw Kirby config option values without redaction. Any bearer with `kirby-mcp:read` can retrieve SMTP passwords, API keys, and other secrets stored in Kirby config.

## Violated Invariant Or Contract

Read scope is used for discovery and low-sensitivity reference material in the HTTP auth model. Runtime config values—including credentials—should require at least runtime scope or a dedicated admin gate, and sensitive keys should be masked before returning to MCP clients.

## Oracle

`HttpScopePolicy::requiredScopes('resources/read', ...)` returns `[READ]` for all URIs (`HttpScopePolicy.php:22-28`). `ConfigGet::run()` calls `$kirby->option($path)` and stringifies the result verbatim (`ConfigGet.php:61-74`). `ConfigResources::configGet()` returns the `line` field directly to the client (`ConfigResources.php:75-77`).

## Counterexample

HTTP bearer scopes: `["kirby-mcp:read"]`.

`resources/read` with `uri: "kirby://config/email.transport.password"` (or `kirby://config/api.key`) returns the plaintext secret string. The same token cannot call `kirby_update_page_content` (requires write) but can read credential-bearing options.

## Why It Might Matter

Least-privilege read tokens—common for documentation/KB clients—can leak production secrets into MCP session logs and connected AI tools. This is a direct confidentiality breach without filesystem or Panel access.

## Proof

**Dataflow trace:** read-scoped bearer → `resources/read` (READ passes) → `ConfigResources::configGet()` → `RuntimeCommandRunner` → `ConfigGet::run()` → `$kirby->option($path)` → plaintext returned in resource body.

No `SecretMasker` or sensitive-key denylist appears on this path (contrast dump masking in `src/Dumps/SecretMasker.php`).

## Counterevidence Checked

Option path normalization in `ConfigGet` rejects empty paths but does not filter sensitive key names. HTTP scope tests validate read-token denial for write tools but not config resource reads. Operators might expect config reads to need runtime scope like other `mcp:config:get` consumers—currently they do not.

## Suggested Next Step

Classify `kirby://config/*` as runtime or admin scope; deny or mask known secret key paths; return structured errors for sensitive options instead of raw values.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-27: open by Devana. Initial report written from static source inspection across all nine trails (`--all`).

DEVANA-KEY: src/Mcp/Resources/ConfigResources.php:44 | config-resource-read-scope-secrets
DEVANA-SUMMARY: open | P1 | high | Read-scoped HTTP tokens can fetch plaintext Kirby config secrets via kirby://config/{option}.