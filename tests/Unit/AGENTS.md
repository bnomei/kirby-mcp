# Unit Test Guidelines

## Mission

Keep fast, deterministic tests for pure logic (parsers, policies, indexing, helpers).

## System

- Unit tests live in `tests/Unit/` and follow the `*Test.php` naming pattern.
- `composer test` runs Pest with `tests/prepend.php` to avoid Kirby helper global conflicts.
- Prefer lightweight fixtures in `tests/fixture/`; avoid depending on `tests/cms/` here.

## Workflows

- Add a unit test for behavior changes that don’t require a real Kirby runtime.
- Run a subset: `vendor/bin/pest tests/Unit/SomeTest.php`.
- Coverage: run `composer cms:starterkit` then `herd coverage ./vendor/bin/pest --coverage` (see `TESTING.md`).

## Guardrails

- No network access.
- Avoid persistent filesystem writes; use temp dirs and clean up.
- Don’t assert incidental ordering unless it’s part of the contract.
- Global `beforeEach` clears Kirby MCP static caches; don’t rely on cached state across tests.
