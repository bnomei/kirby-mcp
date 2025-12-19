# Repository Guidelines

Kirby MCP is a CLI-first MCP server for Kirby CMS (composer-based Kirby projects). This repo contains the PHP library, the
`kirby-mcp` executable, and a bundled Markdown knowledge base.

## Scoped Agent Guides (Keep in Sync)

When changing behavior in a scoped area, update that area’s `AGENTS.md` and any referenced tests/docs (often `README.md`):

- `src/Mcp/AGENTS.md` – MCP tools/resources/contracts
- `src/Cli/AGENTS.md` – Kirby CLI execution/parsing
- `src/Install/AGENTS.md` – runtime install/update semantics
- `commands/mcp/AGENTS.md` – runtime command templates
- `tests/Unit/AGENTS.md` and `tests/Integration/AGENTS.md` – test conventions
- `kb/AGENTS.md` – shipped knowledge base
- `.github/workflows/AGENTS.md` – CI workflows

Note: `tests/cms/` intentionally has no scoped `AGENTS.md` (it’s a fixture sandbox).

## Project Structure & Module Organization

- `src/` – PSR-4 library code (`Bnomei\\KirbyMcp\\*`), grouped by concern: `Mcp/`, `Cli/`, `Project/`, `Blueprint/`,
  `Docs/`, `Dumps/`, `Install/`, `Support/`.
- `bin/kirby-mcp` – development entrypoint (published as `vendor/bin/kirby-mcp` via Composer).
- `commands/mcp/` – Kirby CLI command wrappers installed into host projects by `kirby-mcp install`.
- `kb/` – bundled markdown knowledge base; `knowledge/` – source material and notes.
- `tests/` – Pest tests (`tests/Unit`, `tests/Integration`) with fixtures in `tests/cms` and `tests/fixtures`.

## Build, Test, and Development Commands

- `composer install` – install PHP deps (PHP `^8.2`; CI currently runs on PHP `8.5`).
- `composer mcp` / `bin/kirby-mcp` – run the CLI locally.
- `composer test` – run Pest (prepends `tests/prepend.php`).
- `composer analyse` – run PHPStan (`phpstan.neon.dist`, level 8).
- `composer format` – format PHP via Pint (PSR-12 preset).
- `npm ci && npm run format` – format Markdown/YAML/JS via Prettier.

## Coding Style & Naming Conventions

- Indentation: PHP = 4 spaces; MD/YAML/JSON/JS = 2 spaces (see `.editorconfig`).
- Classes follow PSR-4 (file name matches class); tests end with `Test.php`.
- Don’t commit generated deps/artifacts: `vendor/`, `node_modules/`, `.phpunit.cache/`, `.phpstan.cache/`.

## Testing Guidelines

- Prefer adding/adjusting tests alongside changes; keep unit tests deterministic.
- Put fast logic tests in `tests/Unit`; use `tests/Integration` when Kirby runtime/fixtures are required.

## Commit & Pull Request Guidelines

- Commit history is minimal; use descriptive, imperative messages (optionally `feat:`, `fix:`, `chore:`).
- PRs include: what/why, how to test (`composer test`), and any user-visible changes (CLI output or docs).
- Run `composer format` before pushing; CI may auto-fix styling with Pint.

## Security & Configuration

- Treat anything that executes Kirby CLI or evaluates PHP as sensitive; keep new capabilities gated/allowlisted and
  document defaults and risks in `README.md`.
