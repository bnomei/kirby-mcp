DEVANA-FINDING: v1
DEVANA-STATE: fixed | P1 | high | security=yes
DEVANA-KEY: src/Mcp/OAuth/KirbyOAuthProvider.php:343 | oauth-auth-code-redeem-race

# OAuth authorization codes can be redeemed twice concurrently

## Finding

Authorization code exchange uses read-then-delete without atomic consumption. Two concurrent `POST /token` requests with the same valid code can both pass validation before either deletes the code file, yielding multiple refresh/access token families for a single authorization.

## Violated Invariant Or Contract

Authorization codes must be single-use. OAuth token endpoints must treat code redemption as an atomic consume operation.

## Oracle

`authorizationCodeToken()` reads the code record (`read('auth-codes', $codeId)`), validates fields, then `delete('auth-codes', $codeId)` before `tokenResponse()` (`KirbyOAuthProvider.php:343-368`). `OAuthFileStore::read()` performs unlocked `file_get_contents`; `write()` uses `LOCK_EX` only on its own writes, not on read-delete coordination.

## Counterexample

Two parallel workers (or rapid sequential requests) POST the same `code`, `client_id`, `redirect_uri`, and `code_verifier` after a successful authorize redirect.

1. Worker A: `read` → code valid.
2. Worker B: `read` → code still valid (A has not deleted yet).
3. Both pass PKCE and client checks.
4. Both call `tokenResponse()` → two distinct refresh tokens tied to the same user/scopes.

## Why It Might Matter

Duplicate token issuance expands blast radius of a stolen authorization code window and breaks the single-use assumption relied on by OAuth clients and audit trails.

## Proof

**Sequence:** read → validate → delete is non-atomic across processes. No `rename` to consumed state, no file lock around the full redeem path, no idempotency key.

## Counterevidence Checked

Expired codes are deleted on failed read (`KirbyOAuthProvider.php:345-347`), but that does not serialize concurrent valid reads. Refresh token rotation is separate and does not prevent double initial redemption.

## Suggested Next Step

Implement atomic consume (e.g. locked read-delete in one critical section, or move codes to a consumed bucket via exclusive `rename` before token issuance).

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-27: open by Devana. Initial report written from static source inspection across all nine trails (`--all`).
- 2026-06-27: fixed. Added `OAuthFileStore::take()`, an atomic claim-and-remove that `rename()`s the record file to a unique `.claim` path before reading it. `rename()` is atomic on POSIX filesystems, so exactly one concurrent caller succeeds; the loser gets `null`. `authorizationCodeToken()` now consumes the auth code via `take('auth-codes', $codeId)` up front instead of read → validate → delete, so two concurrent `/token` requests can no longer both pass validation. Removed the now-redundant trailing `delete`. Added unit test `OAuthFileStoreTest` (take returns a record once then null; null for missing) and a double-redeem assertion in the OAuth flow integration test (second redemption → 400 `invalid_grant`). phpstan clean.

DEVANA-KEY: src/Mcp/OAuth/KirbyOAuthProvider.php:343 | oauth-auth-code-redeem-race
DEVANA-SUMMARY: fixed | P1 | high | Concurrent OAuth code redemption can issue multiple token sets because read and delete are not atomic.