# Integration Test Guidelines

## Mission

Validate behavior that depends on Kirby runtime, CLI execution, or the fixture site.

## System

- Fixture Kirby site lives in `tests/cms/` (generated test data).
- The fixture config loads the library before plugin discovery, emulating a Composer-based host while retaining the kit's bundled Kirby version.
- `tests/cms/` must not be committed/changed in PRs; regenerate it locally with `composer cms:starterkit` (CI does this automatically).
- Use `cmsPath()` from `tests/Pest.php` to reference it.
- Integration tests may invoke `bin/kirby-mcp` / Kirby CLI via the same runner used in production code.

## Workflows

- Prefer asserting observable contracts: exit codes, returned arrays/JSON, created command files, rendered output.
- Run just integration tests: `vendor/bin/pest tests/Integration`.
- Coverage: run `composer cms:starterkit` then `herd coverage ./vendor/bin/pest --coverage` (see `TESTING.md`).
- When adding runtime commands/tools, add integration tests for both the CLI command and the MCP tool wrapper.
- For HTTP transport changes, add focused integration coverage for default stdio not entering HTTP mode, `/mcp` POST/GET/DELETE/OPTIONS behavior, `MCP-Session-Id` reuse, auth/origin failures, remote-token public-route guards, insufficient scopes, and unchanged confirm gates for write/eval/query tools.
- OAuth user permissions coverage must exercise real content-update subprocesses with allowed/restricted users, reject unknown users, and verify that request metadata cannot override the authenticated identity. Use an isolated project and clean it up.
- Remote permission coverage must install the real plugin adapter, grant every required ancestor permission explicitly, verify filtered discovery and direct-request denial, and prove role changes are observed without recreating the HTTP handler.
- Remote-token fixtures must bind each token to an existing Kirby `userId`; shared-token and local stdio fixtures remain intentionally unbound trusted access.
- Verify private zero-TTL tool-index reads across users/role changes and legacy subscription GET authorization with a narrower token than the token that created the subscription.
- Include dynamic blueprint allow/deny decisions, NUL-containing subjects, and empty listener token overrides in OAuth regression coverage.
- Activity coverage must exercise dispatched tool and bundled/non-bundled resource reads; discovery, failed lookups, and scope-denied calls must not record. Run Panel browser checks after the PHP suite, which modifies the generated fixture, then rerun `.agents/prepare-panel.php`.

## Guardrails

- Keep `tests/cms/` stable; avoid editing it unless the test explicitly requires it.
- Avoid network calls; tests should pass offline.
- Global `beforeEach` clears Kirby MCP static caches; don’t rely on cached state across tests.
