DEVANA-FINDING: v1
DEVANA-STATE: fixed | P2 | high | security=no
DEVANA-KEY: src/Mcp/Http/HttpScopeMiddleware.php:54 | scope-middleware-scalar-body-typeerror

# Valid JSON-scalar request body throws TypeError in HTTP scope middleware

# Finding

`HttpScopeMiddleware::requiredPostScopes()` decodes the POST body and then calls
`array_is_list($payload)` without checking that `$payload` is an array. A
syntactically valid JSON document that decodes to a scalar or `null` (`null`,
`5`, `true`, `"x"`) is not a `\JsonException`, so it passes the `try/catch`
around `json_decode` (lines 48-52) and reaches `array_is_list($payload)` at
line 54 with a non-array argument.

The file declares `strict_types=1` (line 3), and `array_is_list()` is typed
`array_is_list(array $array): bool`, so passing `int|float|string|bool|null`
raises an uncaught `TypeError`.

## Violated Invariant Or Contract

A syntactically valid JSON-RPC HTTP request must produce a structured response
(JSON-RPC error or normal result), never an uncaught PHP `TypeError`. The
`try/catch` is clearly meant to make malformed/odd bodies fall back to
`[READ]` scope (lines 44-52), but it covers only the decode, not the
`array_is_list` shape assumption.

## Oracle

`array_is_list(array $array): bool` signature + `declare(strict_types=1)`;
`json_decode("null"/"5"/"true", true, 512, JSON_THROW_ON_ERROR)` returns the
scalar without throwing (valid JSON). The neighboring branch already treats a
non-object message defensively inside the loop (`!is_array($message)` → continue,
line 57), showing the code expects non-array shapes — but only *after* the
unguarded `array_is_list` call.

## Counterexample

An authenticated client (valid Bearer; `AuthorizationMiddleware` runs before
`HttpScopeMiddleware` per `HttpMcpHandler.php`) sends:

```
POST /mcp
Authorization: Bearer <valid>

null
```

`trim($body) === 'null'` (non-empty) → `json_decode('null', ...)` returns `null`
(no exception) → `array_is_list(null)` → `TypeError`.

## Why It Might Matter

- Standalone listener (`bin/kirby-mcp http` → `HttpMcpListener::serve`): the
  POST/JSON `$handler->handle($request)` call (`HttpMcpListener.php:99`) is not
  wrapped in try/catch and runs in the single-process accept loop, so an
  uncaught `TypeError` can terminate the loop → DoS of the HTTP listener from a
  single authenticated malformed request.
- Kirby route (`KirbyMcpRoute::handle`, `KirbyMcpRoute.php:75`): the handler
  call is also unwrapped, so the authenticated client receives an HTTP 500
  instead of the spec-required JSON-RPC error.

Authenticated-only, hence P2 rather than P1.

## Proof

Control-flow trace: `HttpScopeMiddleware::process` (POST branch, line 26-27) →
`requiredPostScopes` → `json_decode` succeeds with scalar (lines 48-52 do not
catch) → `array_is_list($payload)` (line 54) with non-array → `TypeError`.

## Counterevidence Checked

- The `\JsonException` catch (line 50) does not intercept this — scalars are
  valid JSON and do not throw.
- No `is_array($payload)` guard exists between decode and `array_is_list`.
- Auth ordering confirmed: scope middleware runs after `AuthorizationMiddleware`,
  so an unauthenticated attacker cannot reach line 54 — impact is limited to
  authenticated callers and (in the bundled listener) shared-token/loopback
  deployments.
- Strongest false-positive reason: a vendor `MiddlewareRequestHandler` might
  wrap middleware exceptions into a 500 response. Even so, the contract
  violation (valid JSON body → server error instead of JSON-RPC error) holds,
  and the crash site is in this repo's own code. Vendor not installed, so the
  listener-crash severity could not be fully confirmed.

## Suggested Next Step

After decode, guard with `if (!is_array($payload)) { return [HttpAuthScopes::READ]; }`
before `array_is_list`.

## Agent Handoff

Preserve the finding body; update line 2 and the `DEVANA-SUMMARY:` prefix.

## Status Notes

- 2026-06-27: open by Devana. Verified statically against
  `HttpScopeMiddleware.php:40-54` (strict_types, unguarded `array_is_list`).

- 2026-06-27: fixed. Added an `if (!is_array($payload)) { return [HttpAuthScopes::READ]; }` guard between the `json_decode` and `array_is_list($payload)` in `HttpScopeMiddleware::requiredPostScopes()`. A syntactically valid JSON-scalar/null body (`null`, `5`, `true`, `"x"`) no longer reaches the typed `array_is_list()` under `strict_types`, so it can't raise an uncaught `TypeError` — it falls back to the READ scope floor like other odd bodies. Added unit test `HttpScopeMiddlewareTest` exercising `process()` with all four scalar body shapes (each reaches the handler → 200 for a read-scoped token). phpstan clean.

DEVANA-KEY: src/Mcp/Http/HttpScopeMiddleware.php:54 | scope-middleware-scalar-body-typeerror
DEVANA-SUMMARY: fixed | P2 | high | A valid JSON-scalar POST body (e.g. `null`) reached array_is_list() unguarded under strict_types, raising an uncaught TypeError. Now guarded with is_array() before array_is_list(), falling back to READ scope.
