DEVANA-FINDING: v1
DEVANA-STATE: open | P2 | medium | security=yes
DEVANA-KEY: src/Mcp/OAuth/KirbyOAuthProvider.php:386 | oauth-single-use-toctou-replay

# OAuth auth-code / refresh-token single-use is non-atomic (TOCTOU double-spend)

## Finding

The built-in OAuth provider enforces single-use of authorization codes and
refresh tokens with a read-check-then-mutate sequence that has no lock spanning
the steps:

- Authorization code: read code record → verify/PKCE → `delete()` on success
  (`KirbyOAuthProvider.php:343-368`).
- Refresh token: read record → check `revoked=false`/not expired → set
  `revoked=true` and rewrite → issue new refresh token
  (`KirbyOAuthProvider.php:386-418`, 442-449).

`OAuthFileStore::read()` is a plain `file_get_contents` with no lock, and
`write()`/`delete()` are separate operations (`OAuthFileStore.php:45-72`). The
`LOCK_EX` in `write()` (`OAuthFileStore.php:32`) locks only the throwaway `.tmp`
file, so it provides no mutual exclusion over the record across the
read→check→delete (or read→check→revoke) window.

## Violated Invariant Or Contract

An OAuth authorization code and a refresh token must each be redeemable exactly
once; a second redemption must yield `invalid_grant`. Refresh-token rotation
depends on atomic invalidation so a replayed token cannot mint a second live
token family.

## Oracle

The code's own design intent: auth codes are `delete()`d and refresh tokens are
`revoked` before re-issue, both to guarantee single use. OAuth 2.1 / RFC 6749
require single-use authorization codes and recommend refresh-token rotation with
reuse detection.

## Counterexample

Two concurrent php-fpm workers, same refresh token `R`:

1. Worker A: `POST /oauth/token grant_type=refresh_token refresh_token=R` →
   reads record → `revoked=false`, not expired → passes checks (lines 387-412).
2. Worker B (before A writes): reads the same record → also `revoked=false` →
   passes checks.
3. Worker A: writes `revoked=true` (line 416), issues access token + new refresh
   token `R_A`.
4. Worker B: overwrites `revoked=true`, issues access token + new refresh token
   `R_B`.
5. Result: one supposedly single-use refresh token produced two live,
   independent token families with valid access tokens; reuse is undetected (no
   family revocation on reuse).

The authorization-code double-spend is the analogous race at lines 344-362: two
concurrent `authorization_code` requests with the same code + correct PKCE both
`read()` before either `delete()`s, both pass PKCE, both mint access tokens.

## Why It Might Matter

Token-family fork from a single refresh token undermines rotation-based reuse
detection and lets a replayed/stolen token coexist with the legitimate one.
Reachable in the documented webserver deployment, where the OAuth provider
routes (`/oauth/token`, `/authorize`, `/register`) are served as ordinary Kirby
routes (`KirbyMcpRoutes::routes()` → `KirbyMcpRoute::handle()` →
`KirbyOAuthProvider`) under concurrent processes.

## Proof

State/event-order trace (above) over the non-atomic read+mutate in the file
store. Reachability proof: provider wired as concurrent webserver routes via
`KirbyMcpRoutes.php` / `KirbyMcpRoute.php`, not only the serialized bundled
listener.

## Counterevidence Checked

- It is a race; single-use is correctly enforced in the serial happy path
  (delete/revoke are otherwise correct).
- The bundled `bin/kirby-mcp` listener handles non-SSE POSTs serially and does
  not even serve the OAuth provider endpoints, so it cannot trigger there — the
  race needs the webserver-route deployment with concurrent workers.
- The OAuth provider is disabled by default (`http.oauthProvider.enabled`),
  bounding exposure → P2 not P1.
- Exploitation requires the attacker to already hold a valid code+verifier or a
  valid refresh token and to race the legitimate redemption.
- Confirmed `OAuthFileStore` has no cross-process lock around read+delete/write
  (read/write/delete all inspected).

## Suggested Next Step

Make redemption atomic: acquire an advisory lock (e.g. `flock` on the record
file or a per-key lock file) spanning read→check→delete/revoke, or use an atomic
compare-and-delete. On detected refresh-token reuse, revoke the whole token
family.

## Agent Handoff

Preserve the finding body; update line 2 and the `DEVANA-SUMMARY:` prefix.

## Status Notes

- 2026-06-27: open by Devana. Verified non-atomic read+mutate in
  `KirbyOAuthProvider.php:343-418` and `OAuthFileStore.php:30-72`; provider
  served as concurrent Kirby routes.

DEVANA-KEY: src/Mcp/OAuth/KirbyOAuthProvider.php:386 | oauth-single-use-toctou-replay
DEVANA-SUMMARY: open | P2 | medium | OAuth auth-code/refresh-token redemption reads-checks-then-mutates with no lock spanning the steps, so concurrent webserver workers can double-spend one code or fork a refresh token into two live token families.
