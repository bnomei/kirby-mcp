DEVANA-FINDING: v1
DEVANA-STATE: open | P2 | medium | security=yes
DEVANA-KEY: src/Mcp/OAuth/KirbyOAuthProvider.php:722 | oauth-redirect-127-prefix

# OAuth redirect URI validation treats `127.*` hostnames as loopback

## Finding

`redirectUrisAreValid()` accepts HTTP redirect URIs whose host starts with `127.`, not only literal loopback addresses. Hostnames like `127.attacker.example` satisfy the check and can receive authorization codes during dynamic client registration.

## Violated Invariant Or Contract

Loopback redirect exceptions (RFC 8252) apply to actual loopback interfaces (`127.0.0.1`, `localhost`, `[::1]`). Prefix matching on `127.` is broader than loopback and allows arbitrary remote hostnames that merely begin with those characters.

## Oracle

`redirectUrisAreValid()` (`KirbyOAuthProvider.php:716-724`) accepts `scheme=http` when `host === 'localhost'`, `host === '127.0.0.1'`, or `str_starts_with($host, '127.')`.

## Counterexample

Register OAuth client with `redirect_uris: ["http://127.evil.example/oauth/callback"]`. Validation returns true. After authorize, `redirectWithCode()` sends the victim's authorization code to that host.

## Why It Might Matter

This weakens redirect URI pinning for HTTP-based OAuth clients and complements session-fixation and broad-scope issuance findings.

## Proof

**Counterexample value:** host `127.evil.example` passes `str_starts_with($host, '127.')` but is not a loopback address.

## Counterevidence Checked

HTTPS redirect URIs require a non-empty host without the `127.` shortcut. Fragment components are rejected. This does not fix mistaken acceptance of deceptive `127.*` DNS names.

## Suggested Next Step

Replace prefix check with explicit allowlist (`localhost`, `127.0.0.1`, `[::1]`) or IP parsing that confirms loopback range membership.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-27: open by Devana. Initial report written from static source inspection across all nine trails (`--all`).

DEVANA-KEY: src/Mcp/OAuth/KirbyOAuthProvider.php:722 | oauth-redirect-127-prefix
DEVANA-SUMMARY: open | P2 | medium | OAuth redirect validation accepts http://127.* hostnames that are not real loopback addresses.