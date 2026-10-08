# Contributing

Thanks for helping improve Kirby MCP.

## Quick Start

- Open or comment on an issue for significant changes.
- Fork the repo and create a topic branch from `main`.
- Install dependencies: `composer install` and `npm ci`.
- Run tests: `composer test` (use `composer analyse` for static analysis).
- Format code: `composer format` and `npm run format`.

If integration tests require a Kirby fixture, run `composer cms:plainkit` first.

## Pull Requests

- Keep PRs focused and describe the why and the how.
- Update docs/tests when behavior changes.
- Add every new tool/resource to `Mcp\Permissions` and [the capability map](docs/permissions.md).
  Verify remote discovery and direct-call enforcement without changing trusted local behavior.
- Follow repo guidelines in `AGENTS.md`.

## Releases

- Record the version and date in `CHANGELOG.md`; Composer versions come from annotated `vX.Y.Z` Git tags, not a manifest version field.
- Validate tests, static analysis, and formatting before committing and pushing the release commit and tag together.
- OAuth changes also need browser validation of native Panel login, second-factor completion, consent, and token exchange; keep real credentials and tokens out of logs.

## Code of Conduct

Be kind and constructive in issues, reviews, and discussions.
