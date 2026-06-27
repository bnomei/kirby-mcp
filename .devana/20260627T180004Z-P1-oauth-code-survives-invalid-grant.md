DEVANA-FINDING: v1
DEVANA-STATE: fixed | P1 | high | security=yes
DEVANA-KEY: src/Mcp/OAuth/KirbyOAuthProvider.php:350 | oauth-code-survives-invalid-grant

# OAuth authorization codes remain redeemable after client, redirect, or PKCE mismatch

## Finding

`authorizationCodeToken()` deletes an authorization code only when it is missing/expired (line 346) or after all validations succeed (line 362). Failed redemption due to wrong `client_id`, `redirect_uri`, or PKCE leaves the code stored until TTL expiry, despite returning `invalid_grant`.

## Violated Invariant Or Contract

Authorization codes are single-use credentials. A failed token exchange should burn the code so it cannot be retried by another party or after further guessing.

## Oracle

- OAuth 2.0 practice: `invalid_grant` on code exchange typically invalidates the code.
- `KirbyOAuthProvider::authorizationCodeToken()` lines 350–360 return errors without `delete('auth-codes', $codeId)`.
- Separate report `oauth-auth-code-redeem-race` covers concurrent successful redemption; this covers failed-validation survival.

## Counterexample

1. Victim completes consent; auth code `C` is issued to client `A` with PKCE challenge `S256(...)`.
2. Attacker intercepts `C` (referrer leak, proxy log, etc.).
3. Attacker POSTs `/token` with `code=C`, correct `client_id` for `A`, wrong `code_verifier` → `invalid_grant` "PKCE verification failed." Code **not** deleted.
4. Until `expires_at`, attacker retries with guessed/stolen `code_verifier`, or victim's legitimate client redeems successfully—both remain possible while the code lives.

Same for wrong `client_id` or `redirect_uri`: error returned, code retained.

## Why It Might Matter

Extends the window for authorization-code theft and brute-force PKCE attempts. Complements the concurrent-redeem race: even serial misuse paths keep the code hot.

## Proof

**Control-flow trace:** `read('auth-codes', $codeId)` → validation branch fails at lines 350, 354, or 358 → early `return oauthError(...)` → no `delete` → code file remains in `.kirby-mcp/oauth/auth-codes/`.

**State transition mismatch:** HTTP response signals invalid grant; persisted state still shows code as unredeemed.

## Counterevidence Checked

- Expired/missing codes are deleted on read (line 346).
- Successful redemption deletes before issuing tokens (line 362).
- Codes are short-lived (600s TTL in provider); impact bounded by TTL but non-zero.
- Concurrent double-success race is a distinct bug already reported.

## Suggested Next Step

Delete (or mark consumed) the authorization code on every `invalid_grant` path after the code ID is resolved, or use atomic compare-and-delete on first exchange attempt.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-27: fixed (resolved by the `oauth-auth-code-redeem-race` fix). `authorizationCodeToken()` now consumes the authorization code atomically via `OAuthFileStore::take()` (rename-based POSIX-atomic claim) as the very first step — BEFORE any `client_id`, `redirect_uri`, or PKCE validation. Because the code record is removed the instant it is read, every failure path (wrong client, wrong redirect, failed PKCE) returns `invalid_grant` with the code already gone; it cannot be retried or redeemed afterwards. This closes both the failed-validation-survival window described here and the concurrent double-success race. Added a regression block to the Integration OAuth flow test (`it serves the built-in OAuth provider flow ...`): a fresh code redeemed with a wrong `code_verifier` returns `invalid_grant`, and a follow-up attempt on the same code with the *correct* verifier also returns `invalid_grant` — proving the failed attempt burned the code. Full `KirbyMcpRouteTest` suite passes (21 tests / 134 assertions); phpstan clean.

- 2026-06-27: open by Devana. Initial report written from static source inspection.

DEVANA-KEY: src/Mcp/OAuth/KirbyOAuthProvider.php:350 | oauth-code-survives-invalid-grant
DEVANA-SUMMARY: fixed | P1 | high | OAuth authorization codes are not deleted on client, redirect, or PKCE mismatch, so invalid_grant responses still leave codes redeemable until expiry.