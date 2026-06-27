DEVANA-FINDING: v1
DEVANA-STATE: fixed | P0 | high | security=yes
DEVANA-KEY: src/Mcp/KirbyMcpRoute.php:50 | shared-token-proxy-bypass

# Shared-token HTTP auth reachable behind reverse proxy

## Finding

`KirbyMcpRoute` treats shared-token mode as loopback-safe when `REMOTE_ADDR` is loopback, but it does not require a loopback request host. On a typical reverse-proxy deployment, PHP sees `REMOTE_ADDR=127.0.0.1` while the client reaches the public site URL, so shared-token MCP becomes reachable from the internet.

## Violated Invariant Or Contract

`src/Mcp/AGENTS.md` and `README.md` state that shared-token HTTP auth is loopback/local-development only. `remote-token` mode already rejects proxied public requests (`REMOTE_ADDR=127.0.0.1` with a public host) unless HTTPS is used; shared-token has no equivalent host check.

## Oracle

- `src/Mcp/AGENTS.md` line 66: shared-token is loopback-only on the Kirby route.
- `tests/Integration/KirbyMcpRouteTest.php` rejects remote-token when `REMOTE_ADDR=127.0.0.1` and host is `example.test`, but has no matching shared-token proxy test.
- `KirbyMcpRoute::isLoopbackRequest()` combines `REMOTE_ADDR` and host for remote-token paths, while shared-token only calls `isLoopbackRemoteAddress()`.

## Counterexample

1. Kirby site serves `/mcp` via nginx → php-fpm with `http.auth.mode=shared-token` and a known shared token.
2. External client sends `POST https://victim.example/mcp` with `Authorization: Bearer <shared-token>`.
3. PHP receives `REMOTE_ADDR=127.0.0.1`, `Host=victim.example`.
4. `KirbyMcpRoute::handle()` passes the shared-token gate and starts the full project MCP surface.

## Why It Might Matter

Shared-token mode is documented for local development. Behind a reverse proxy it can expose the entire MCP API (content reads/writes, query, eval if enabled, config resources) to anyone who knows or guesses the bearer secret.

## Proof

Cross-entry mismatch / control-flow trace:

- Shared-token gate: `isLoopbackRemoteAddress($request) === false` only (`KirbyMcpRoute.php` lines 50–55).
- Remote-token gate: `isLoopbackRequest()` requires loopback `REMOTE_ADDR` **and** loopback host (`KirbyMcpRoute.php` lines 57–63, 169–173).
- Test coverage gap: `KirbyMcpRouteTest.php` lines 324–342 cover remote-token proxy rejection; shared-token has no analogous case.

## Counterevidence Checked

- Non-loopback `REMOTE_ADDR` is rejected for shared-token (test at lines 215–233).
- Spoofed `Host: 127.0.0.1` with public `REMOTE_ADDR` is rejected (test at lines 236–255).
- Bearer token is still required; this is not an unauthenticated bypass.
- Counterevidence does not block the proxied-public-host + loopback-`REMOTE_ADDR` combination.

## Suggested Next Step

Align shared-token loopback detection with `isLoopbackRequest()` (or equivalent host/origin checks) and add an integration test mirroring the remote-token proxy case.

## Agent Handoff

After working this report, preserve the original finding body. Update line 2 `DEVANA-STATE: ...` and the final `DEVANA-SUMMARY:` status/priority/confidence prefix. Use one of: `open`, `fixed`, `invalid`, `stale`, `duplicate`, `wontfix`. Keep `DEVANA-KEY:` stable unless the same finding moved. Add dated notes below with evidence checked.

## Status Notes

- 2026-06-25: open by Devana. Initial report written from static source inspection.
- 2026-06-27: fixed. Shared-token gate in `KirbyMcpRoute::handle()` now calls `isLoopbackRequest()` (loopback REMOTE_ADDR **and** loopback host) instead of `isLoopbackRemoteAddress()`, matching the remote-token gate. Reverse-proxied public requests (`REMOTE_ADDR=127.0.0.1`, public host) are now rejected with 503. Added integration test `it('rejects shared-token public requests forwarded from local reverse proxies')`. Full `KirbyMcpRouteTest` suite passes (19 tests).
- 2026-06-27: reopened. The fix blocks the documented `Host=victim.example` counterexample, but the replacement host check still uses `str_starts_with($host, '127.')` in `KirbyMcpRoute::isLoopbackHost()`. A reverse-proxied request with `REMOTE_ADDR=127.0.0.1` and `Host=127.0.0.1.evil.com` still satisfies `isLoopbackRequest()`, so an attacker-controlled public `127.*` hostname can pass the shared-token loopback gate if routed to the site. `KirbyMcpHttpConfig::isLoopbackHost()` has the same prefix behavior and treats `host=127.0.0.1.evil.com` shared-token config as valid. Repro evidence: reflection call to `KirbyMcpRoute::isLoopbackHost('127.0.0.1.evil.com')` returned `true`; `KirbyMcpHttpConfig(... host: '127.0.0.1.evil.com', authMode: shared-token ...)` returned no validation errors.
- 2026-06-27: fixed. `KirbyMcpRoute::isLoopbackHost()` and `KirbyMcpHttpConfig::isLoopbackHost()` now accept only `localhost`, `::1`, or a valid IPv4 literal in `127.0.0.0/8`; DNS names that merely begin with `127.` are non-loopback. `isLoopbackRemoteAddress()` now reuses the same predicate, so the route gate requires both the socket peer and request host to be real loopback values. Added regressions for `Host=127.0.0.1.evil.com` behind a local reverse proxy and for shared-token config `host=127.0.0.1.evil.com`. Focused verification passed: `php -d auto_prepend_file=tests/prepend.php vendor/bin/pest tests/Unit/KirbyMcpHttpConfigTest.php tests/Unit/McpHttpAuthTest.php tests/Integration/KirbyMcpRouteTest.php` (42 tests / 219 assertions).

DEVANA-KEY: src/Mcp/KirbyMcpRoute.php:50 | shared-token-proxy-bypass
DEVANA-SUMMARY: fixed | P0 | high | Shared-token HTTP MCP now requires real loopback REMOTE_ADDR and request host values; public hostnames that start with 127. no longer satisfy the loopback gate.
