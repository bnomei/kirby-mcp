DEVANA-FINDING: v1
DEVANA-STATE: open | P3 | high | security=yes
DEVANA-KEY: src/Mcp/Http/HttpOriginPolicy.php:62 | origin-loopback-prefix-rebinding

# Loopback Origin allowlist uses `127.` prefix match (DNS-rebinding bypass)

## Finding

`HttpOriginPolicy::isLoopbackOrigin()` accepts any origin whose host *starts
with* `127.` (`HttpOriginPolicy.php:59-62`):

```php
return $host === 'localhost' || $host === '::1' || $host === '127.0.0.1'
    || str_starts_with($host, '127.');
```

When no explicit `allowedOrigins` are configured (the default), this is the
anti-DNS-rebinding boundary for the loopback-bound `/mcp` server. The prefix
match treats attacker-controlled hostnames such as `127.0.0.1.evil.com` or
`127.evil.com` as loopback.

The same flawed prefix check is duplicated in the OAuth provider's
`redirect_uri` "loopback HTTP" validation
(`KirbyOAuthProvider.php:722`, `redirectUrisAreValid`), permitting plaintext-HTTP
redirect targets on `127.*`-named non-loopback hosts.

## Violated Invariant Or Contract

The default Origin check must accept only true loopback origins
(`127.0.0.0/8`, `localhost`, `::1`, `127.0.0.1`), not arbitrary hostnames that
textually begin with `127.`. This is the control that defends a loopback server
against DNS-rebinding.

## Oracle

Intent of `isLoopbackOrigin` (exact `localhost`/`::1`/`127.0.0.1` are matched
exactly; the `127.` branch is meant to cover `127.0.0.0/8`). Correct membership
is IP-range / exact-host, not substring/prefix.

## Counterexample

`Origin: http://127.0.0.1.evil.com` → `parse_url` host = `127.0.0.1.evil.com`
→ `str_starts_with($host, '127.')` true → `allows()` returns true. (Also
`http://127.evil.com:8080`.) An attacker page on `127.0.0.1.evil.com` (DNS
controlled by the attacker, A-record rebindable to `127.0.0.1`) passes the
Origin check after rebind.

## Why It Might Matter

The classic DNS-rebinding mitigation (reject non-loopback Origins) is defeated.
P3 because every `/mcp` request still requires a valid Bearer token
(`AuthorizationMiddleware` returns an always-deny validator when none is
configured), so the Origin bypass alone does not yield tool execution unless the
attacker's JS also possesses a valid token — but it removes a named
defense-in-depth control. The OAuth `redirect_uri` variant additionally allows
plaintext-HTTP code delivery to a `127.*`-named host.

## Proof

Counterexample value + reachable path: `HttpMcpHandler::validateOrigin` →
`HttpOriginPolicy::allows('http://127.0.0.1.evil.com')` → loopback branch (line
62) → allowed. Confirmed `parse_url` host extraction + `str_starts_with`
semantics.

## Counterevidence Checked

- Bearer auth still gates `/mcp` (no anonymous path past
  `AuthorizationMiddleware`), so this is a weakened control, not direct RCE →
  P3.
- A missing/empty Origin is allowed (lines 20-22) — standard for non-browser
  MCP clients and still Bearer-gated; browsers cannot omit Origin cross-origin,
  so not browser-exploitable on its own.
- `KirbyOAuthProvider::isLoopbackRequest` (line 1054) uses the same prefix but
  against `REMOTE_ADDR` (the real socket peer, not spoofable to a hostname), so
  that instance is not exploitable.
- Strongest false-positive reason: it is defense-in-depth behind Bearer — but
  the trail explicitly targets "substring match instead of exact," and the
  control is genuinely defeated for its stated purpose.

## Suggested Next Step

Match loopback exactly: allow `localhost`, `::1`, and hosts that
`filter_var($host, FILTER_VALIDATE_IP)` confirms are in `127.0.0.0/8` (and `::1`).
Apply the same fix to `KirbyOAuthProvider::redirectUrisAreValid` (line 722).

## Agent Handoff

Preserve the finding body; update line 2 and the `DEVANA-SUMMARY:` prefix.

## Status Notes

- 2026-06-27: open by Devana. Verified `HttpOriginPolicy.php:62` prefix match;
  same pattern in `KirbyOAuthProvider.php:722`.

DEVANA-KEY: src/Mcp/Http/HttpOriginPolicy.php:62 | origin-loopback-prefix-rebinding
DEVANA-SUMMARY: open | P3 | high | The default loopback Origin check uses str_starts_with($host,'127.'), so attacker hostnames like 127.0.0.1.evil.com pass, defeating DNS-rebinding protection (same flaw in OAuth redirect_uri validation).
