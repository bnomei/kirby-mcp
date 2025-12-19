# Integration Test Guidelines

## Mission
Validate behavior that depends on Kirby runtime, CLI execution, or the fixture site.

## System
- Fixture Kirby site lives in `tests/cms/` (treated as test data).
- Use `cmsPath()` from `tests/Pest.php` to reference it.
- Integration tests may invoke `bin/kirby-mcp` / Kirby CLI via the same runner used in production code.

## Workflows
- Prefer asserting observable contracts: exit codes, returned arrays/JSON, created command files, rendered output.
- Run just integration tests: `vendor/bin/pest tests/Integration`.

## Guardrails
- Keep `tests/cms/` stable; avoid editing it unless the test explicitly requires it.
- Avoid network calls; tests should pass offline.

