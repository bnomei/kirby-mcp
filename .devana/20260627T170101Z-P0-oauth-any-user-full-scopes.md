DEVANA-FINDING: v1
DEVANA-STATE: open | P0 | high | security=yes
DEVANA-KEY: src/Mcp/OAuth/KirbyOAuthProvider.php:176 | oauth-any-user-full-scopes

# Built-in OAuth issues full MCP scopes to any Panel user

## Finding

When the built-in OAuth provider is enabled, any Kirby Panel user who can log in can authorize a client for all MCP scopes (read, runtime, write, execute, admin) if the authorize request omits `scope` and `http.scopes` is unset. There is no role or admin check. Unauthenticated dynamic client registration is also supported, and `consent: auto` skips the consent UI entirely.

## Violated Invariant Or Contract

OAuth consent is documented as an explicit approve/deny step by default (`src/Mcp/AGENTS.md`). Scope issuance should reflect server policy (`http.scopes`) and the authorizing principal's trust level. A low-privilege Panel account should not be able to mint tokens with admin/write/execute capabilities solely by logging in.

## Oracle

`KirbyOAuthProvider::authorize()` checks only that `Kirby::instance()->user()` is non-null (`KirbyOAuthProvider.php:160-187`). `finalizeScopesForClient()` with empty `scope` returns `clientScopes()`, which falls back to `allowedScopes()` (`KirbyOAuthProvider.php:757-762`, `798-810`). When `config->scopes === []`, `allowedScopes()` returns `HttpAuthScopes::all()`.

## Counterexample

1. Enable `http.oauthProvider.enabled` with default `consent: auto` and empty `http.scopes`.
2. Attacker `POST /mcp/oauth/register` (no auth) with `redirect_uris: ["https://evil.example/callback"]`.
3. Start authorize flow with empty `scope` for the new `client_id`.
4. Any Panel user completes login (or is already logged in with `consent: auto`).
5. Issued JWT contains all scopes; bearer can call `kirby_update_page_content`, `kirby_eval`, `kirby_cache_clear`.

## Why It Might Matter

A compromised or low-trust Panel account, or a victim tricked through OAuth login, can grant external MCP clients full project mutation and code-execution capability. This is catastrophic when HTTP MCP is exposed beyond a tightly trusted admin group.

## Proof

**Control-flow trace:** `registerClient()` (unauthenticated) → `authorize()` → `finalizeScopesForClient('', $client)` → empty requested scope → `clientScopes()` → `allowedScopes()` → `HttpAuthScopes::all()` → `tokenResponse()` embeds full scope list.

**State transition:** Panel user presence is the only gate between anonymous client registration and full-scope token issuance.

## Counterevidence Checked

PKCE is required for public clients. Redirect URI scheme validation exists (separate finding for `127.*` hostnames). CSRF protects login/consent POST forms. None of these restrict which Kirby user roles may authorize or cap issued scopes below `HttpAuthScopes::all()` when config scopes are empty.

## Suggested Next Step

Require admin (or configurable) Panel role for authorization; cap default issued scopes; disable unauthenticated DCR in non-loopback deployments; treat empty authorize `scope` as minimum read-only unless explicitly widened by an admin consent screen.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-27: open by Devana. Initial report written from static source inspection across all nine trails (`--all`).

DEVANA-KEY: src/Mcp/OAuth/KirbyOAuthProvider.php:176 | oauth-any-user-full-scopes
DEVANA-SUMMARY: open | P0 | high | Any Panel user can authorize OAuth clients for all MCP scopes when scope is omitted and http.scopes is unset.