# Unit Test Guidelines

## Mission

Keep fast, deterministic tests for pure logic (parsers, policies, indexing, helpers).

## System

- Unit tests live in `tests/Unit/` and follow the `*Test.php` naming pattern.
- `composer test` runs Pest with `tests/prepend.php` to avoid Kirby helper global conflicts.
- Prefer lightweight fixtures in `tests/fixture/`; avoid depending on `tests/cms/` here.

## Workflows

- Add a unit test for behavior changes that don’t require a real Kirby runtime.
- For HTTP transport changes, keep auth, Origin, scope mapping, shared-token loopback restrictions, remote-token hash validation, token metadata, and session-header helpers in unit tests where possible.
- Keep the permission map exhaustive against MCP attributes; test parent denial, URI-template boundaries, and direct operation checks independently of discovery filtering.
- Custom command tests cover invalid definitions, reserved names/targets, project-only indexes, CLI permission filtering and execute/write HTTP scope denial.
- Cover reference-bearing schema rejection, short fixed aliases and the registered custom handler's raw write/required/type checks independently of SDK validation.
- Keep activity tracking tests deterministic with explicit timestamps; cover threshold boundaries, malformed/future data, successful-result filtering, and preservation of delegated response metadata.
- Run a subset: `vendor/bin/pest tests/Unit/SomeTest.php`.
- Coverage: run `composer cms:starterkit` then `herd coverage ./vendor/bin/pest --coverage` (see `TESTING.md`).

## Guardrails

- No network access.
- SDK v0.9 auth tests use validated `AccessToken` objects; legacy `oauth.*` attributes must not grant scopes. Elicitation probes use typed `RequestSuspension` properties.
- Cover unsupported runtime versions without blocking global-reference access; new schema guides must not be assumed present merely because a field appears in the Panel catalog.
- Avoid persistent filesystem writes; use temp dirs and clean up.
- Don’t assert incidental ordering unless it’s part of the contract.
- Global `beforeEach` clears Kirby MCP static caches; don’t rely on cached state across tests.
- Keep native browse/read/upload/move capabilities exhaustive with discovery, and distinguish children/file pagination resource permissions. Upload/move scope mappings must require write rather than falling through to read.
- CLI validation tests must cover canonical names and rejection of traversal, separator aliases and encoding before both policy evaluation and runner binary resolution.
