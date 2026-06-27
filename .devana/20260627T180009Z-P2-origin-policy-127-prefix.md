DEVANA-FINDING: v1
DEVANA-STATE: open | P2 | medium | security=yes
DEVANA-KEY: src/Mcp/Http/HttpOriginPolicy.php:62 | origin-policy-127-prefix

# Default Origin policy accepts hostnames starting with `127.` beyond loopback

## Finding

When `http.allowedOrigins` is empty, `HttpOriginPolicy` falls back to `isLoopbackOrigin()`, which accepts any host where `str_starts_with($host, '127.')`. Hostnames like `127.0.0.1.evil.com` pass the check. Combined with credentialed browser requests and a leaked Bearer token, a malicious page can trigger cross-origin POSTs that pass Origin validation.

## Violated Invariant Or Contract

Loopback Origin allowlisting should admit only true loopback addresses (`127.0.0.1`, `::1`, `localhost`), not arbitrary domains whose names merely prefix-match `127.`.

## Oracle

- `HttpOriginPolicy::isLoopbackOrigin()` lines 59–62: explicit `127.0.0.1` plus `str_starts_with($host, '127.')`.
- `KirbyMcpRoute` applies Origin check before Bearer auth on `/mcp` requests.
- Related `oauth-redirect-127-prefix` report covers redirect URI validation; this is the CORS/Origin gate on MCP requests.

## Counterexample

1. MCP HTTP listener on `127.0.0.1:8765` with empty `allowedOrigins` (default dev setup).
2. Attacker hosts `https://127.0.0.1.evil.com` (attacker-controlled DNS).
3. Victim browser page at that origin runs `fetch('http://127.0.0.1:8765/mcp', {method:'POST', headers:{Authorization:'Bearer <leaked>', Origin:'http://127.0.0.1.evil.com', ...}, body: ...})`.
4. `HttpOriginPolicy::allows()` → true (host starts with `127.`).
5. Request reaches MCP handler if token is valid.

## Why It Might Matter

Defense-in-depth for browser-connected MCP clients. Token theft via XSS or extension remains the primary bar, but loose Origin matching expands the attack surface for credentialed fetches to local MCP listeners.

## Proof

**Counterexample value:** Origin `http://127.0.0.1.evil.com` → `parse_url` host `127.0.0.1.evil.com` → `str_starts_with(..., '127.')` true.

**Cross-entry mismatch:** OAuth redirect validation has a parallel loose `127.` check (`oauth-redirect-127-prefix`); HTTP Origin gate repeats the pattern in a separate trust boundary.

## Counterevidence Checked

- Valid Bearer token still required; empty Origin is allowed for non-browser clients (separate policy choice).
- Explicit `allowedOrigins` list bypasses loopback fallback and uses exact match.
- 403 responses for failed auth omit reflected CORS on unauthorized bodies; Origin check runs before handler for allowed origins.
- DNS rebinding to true `127.0.0.1` is a broader class; this bug additionally accepts non-loopback hostnames by string prefix.

## Suggested Next Step

Replace `str_starts_with($host, '127.')` with strict equality for `127.0.0.1` or parse IP octets; optionally require exact Origin entries for browser deployments.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-27: open by Devana. Initial report written from static source inspection.

DEVANA-KEY: src/Mcp/Http/HttpOriginPolicy.php:62 | origin-policy-127-prefix
DEVANA-SUMMARY: open | P2 | medium | HttpOriginPolicy treats any host starting with 127. as loopback, allowing credentialed cross-origin MCP requests from attacker domains like 127.0.0.1.evil.com.