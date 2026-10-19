# Integration Test Guidelines

## Mission

Validate behavior that depends on Kirby runtime, CLI execution, or the fixture site.

## System

- Fixture Kirby site lives in `tests/cms/` (generated test data).
- `tests/fixture/prepare-kit.sh` pins Kirby 6.0.0-beta.1 kits and verifies the bundled runtime against the Composer lock before replacing the fixture. Do not use moving kit branches.
- The fixture config loads the library before plugin discovery, emulating a Composer-based host while retaining the kit's bundled Kirby version.
- `tests/cms/` must not be committed/changed in PRs; regenerate it locally with `composer cms:starterkit` (CI does this automatically).
- Use `cmsPath()` from `tests/Pest.php` to reference it.
- Integration tests may invoke `bin/kirby-mcp` / Kirby CLI via the same runner used in production code.

## Workflows

- Prefer asserting observable contracts: exit codes, returned arrays/JSON, created command files, rendered output.
- Run just integration tests: `vendor/bin/pest tests/Integration`.
- Coverage: run `composer cms:starterkit` then `herd coverage ./vendor/bin/pest --coverage` (see `TESTING.md`).
- When adding runtime commands/tools, add integration tests for both the CLI command and the MCP tool wrapper.
- Custom-tool stdio coverage must execute a real project command, preserve argv values, reject undeclared/invalid inputs and missing write opt-in, retain deny precedence and report non-zero exits. Restore fixture configuration and remove temporary commands.
- Custom discovery regressions include numeric metadata in fuzzy suggestions and missing required inputs with schema defaults; defaults must not cause execution with missing raw arguments.
- For HTTP transport changes, add focused integration coverage for default stdio not entering HTTP mode, `/mcp` POST/GET/DELETE/OPTIONS behavior, `MCP-Session-Id` reuse, auth/origin failures, remote-token public-route guards, insufficient scopes, and unchanged confirm gates for write/eval/query tools.
- Cover unauthenticated OPTIONS with origin/query-credential restrictions, resource-specific OAuth metadata paths, and modern stdio calls without initialization. Capture stateless SSE at the subprocess stdout boundary because SDK v0.9 flushes PHP output buffers; publish only after subscription acknowledgment.
- Exercise malformed Host requests followed by successful authentication in the same listener process, including 127.0.0.2. External OAuth fixtures must prove positive authentication for discovery and explicit JWKS URLs, plus signature/issuer/audience/expiry and unsupported-algorithm rejection; negative-only tests cannot prove working validation.
- OAuth user permissions coverage must exercise real content-update subprocesses with allowed/restricted users, reject unknown users, and verify that request metadata cannot override the authenticated identity. Use an isolated project and clean it up.
- Remote permission coverage must install the real plugin adapter, grant every required ancestor permission explicitly, verify filtered discovery and direct-request denial, and prove role changes are observed without recreating the HTTP handler.
- Remote-token fixtures must bind each token to an existing Kirby `userId`; shared-token and local stdio fixtures remain intentionally unbound trusted access.
- Verify private zero-TTL tool-index reads across users/role changes and legacy subscription GET authorization with a narrower token than the token that created the subscription.
- Include dynamic blueprint allow/deny decisions, NUL-containing subjects, and empty listener token overrides in OAuth regression coverage.
- Exercise Panel OAuth handoff, browser-session binding, expiry, replacement, and consumption on approve/deny. Verify real Panel login and token exchange in the browser fixture; impersonation alone does not verify login redirects.
- Activity coverage must exercise dispatched tool and bundled/non-bundled resource reads; discovery, failed lookups, and scope-denied calls must not record. Run Panel browser checks after the PHP suite, which modifies the generated fixture, then rerun `.agents/prepare-panel.php`.
- Kirby 6 coverage must distinguish picker value fields from display lists, preserve raw root references, resolve nested block fields, and verify invalid options with validation on/off plus independent language storage. Query UUID strings use `Uuid::from()`.

## Guardrails

- Keep `tests/cms/` stable; avoid editing it unless the test explicitly requires it.
- Avoid network calls; tests should pass offline.
- Global `beforeEach` clears Kirby MCP static caches; don’t rely on cached state across tests.
- Integration teardown commits the active Kirby session because tests share a PHP process. Commit any other App's session before replacing it; otherwise a later App can deadlock on the same cookie session's write lock.
- Native content tests use isolated projects and actual runtime subprocesses. Check byte fidelity, real MCP image contents, pagination links and UUID-disabled paths; verify upload context/capacity/accept rules, preserved sources, single-use bearer uploads and permission revocation at redemption, plus preview/confirmed moves and cycles. Reject filesystem/traversal inputs and symlink aliases; exercise both UUID/path IDs with native file and parent read/access denial.
- Cover case-insensitive reserved upload metadata before preview/tickets and template capacity after a fresh runtime reload. Assert READ-only HTTP denial for IDs containing `update-schema`, with authorized-read controls. Thumbnail fallback must error without saving original metadata or returning image content.
- CLI help regressions must prove out-of-root marker PHP is not loaded, with canonical command controls. OAuth coverage must include omitted scopes after policy narrowing, replay revocation of descendants but not unrelated grants, competing refresh workers, rejection of legacy refresh tokens, and cleartext OAuth MCP requests with explicit HTTPS metadata.
