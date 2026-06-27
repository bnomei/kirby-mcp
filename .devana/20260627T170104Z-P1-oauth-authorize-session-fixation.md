DEVANA-FINDING: v1
DEVANA-STATE: fixed | P1 | high | security=yes
DEVANA-KEY: src/Mcp/OAuth/KirbyOAuthProvider.php:536 | oauth-authorize-session-fixation

# OAuth authorize session fixation binds victim logins to attacker clients

## Finding

The built-in OAuth provider stores authorization request parameters server-side and later restores them from a `session` query parameter. An attacker who obtains or assigns a session id can bind a victim's Panel login to the attacker's `client_id` and `redirect_uri`, causing authorization codes (and tokens) for the victim's Kirby account to be delivered to the attacker-controlled redirect endpoint.

## Violated Invariant Or Contract

OAuth authorization parameters (`client_id`, `redirect_uri`, `scope`) must be bound to the authenticated user's intentional consent. Resuming an authorize flow from a server-issued session token must not let a third party substitute their client registration for a victim's consent.

## Oracle

`authorize()` for unauthenticated users writes `{params: $params}` to `sessions/{sessionId}` and redirects to login with that id (`KirbyOAuthProvider.php:161-168`). After login, `authorizationParams()` prefers stored session params over fresh query values when `?session=` is present (`KirbyOAuthProvider.php:536-545`).

## Counterexample

1. Attacker registers OAuth client with `redirect_uri=https://evil.example/cb`.
2. Attacker opens `/mcp/oauth/authorize?client_id=evil&redirect_uri=...&response_type=code` (not logged in). Server creates `session=S1` storing attacker's params; redirects to login.
3. Attacker tricks victim into visiting `/mcp/oauth/login?session=S1` (or POST login with that session).
4. Victim authenticates; redirect to `/mcp/oauth/authorize?session=S1`.
5. `authorizationParams()` loads attacker's `client_id`/`redirect_uri`; victim's Kirby user id is used in `redirectWithCode()` to attacker's redirect.

With `consent: auto` or remembered consent, the victim may never see the attacker's client name.

## Why It Might Matter

This is a classic OAuth session-fixation pattern. Any Panel user who can be nudged through a login link can unknowingly grant MCP tokens to an external application controlled by an attacker.

## Proof

**State transition mismatch:** login session `S1` carries attacker OAuth params → authenticated victim session → authorization code issued for victim `user_id` with attacker's `client_id` and `redirect_uri`.

**Control-flow:** `authorizationParams()` session branch returns stored params without re-validating they match the currently authenticated user's intended authorize URL.

## Counterevidence Checked

CSRF protects the login POST form but not the victim visiting a crafted login URL. PKCE protects token exchange but not issuance of the authorization code to the attacker's redirect. Consent UI would mitigate only when shown and when the victim reads client details.

## Suggested Next Step

Bind authorize sessions to a signed state nonce covering `client_id`, `redirect_uri`, and `scope`; discard stored params after login and require the user to restart authorize from the client's signed request; always show consent with immutable client metadata.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-27: open by Devana. Initial report written from static source inspection across all nine trails (`--all`).
- 2026-06-27: fixed (silent-vector closed; defense-in-depth). `KirbyOAuthProvider::authorize()` now forces an explicit consent screen whenever the flow is resumed from a server-stored login session (`isResumedFromLoginSession()` — `?session=` present and loaded), overriding `consent: auto` and remembered consent. This removes the silent fixation vector: a victim who is nudged through `/login?session=S1` can no longer issue a code to the attacker's `redirect_uri` without first seeing the immutable client metadata (client name + scopes) and explicitly approving. The login session is also made single-use: `completeConsent()` calls `consumeLoginSession()` (deletes `sessions/{id}`) once the code is issued, preventing replay. Added integration test `it('forces explicit consent when an authorize flow is resumed from a login session')` (unauth authorize → login redirect with session id → victim resume with `consent=auto` → 200 consent form for "Evil Connector", not a silent 302 code). phpstan clean; existing OAuth flow tests still pass.
  - Residual (acknowledged): full cryptographic binding of the authorize session to the victim's browser (signed state nonce / cookie) is not implemented; the mitigation relies on the now-mandatory consent screen for resumed flows. Operators wanting stronger binding should keep `consent` at `snippet`/`always` (the default is `snippet`) and avoid `auto` on internet-exposed deployments.

DEVANA-KEY: src/Mcp/OAuth/KirbyOAuthProvider.php:536 | oauth-authorize-session-fixation
DEVANA-SUMMARY: fixed | P1 | high | OAuth login sessions restore attacker-stored authorize params so victims can issue codes to attacker redirect URIs.