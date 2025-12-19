# CI Workflow Guidelines

## Mission
Keep GitHub Actions fast, reproducible, and aligned with local `composer` scripts.

## System
- `pest-tests.yml` runs Pest on PHP 8.5 (with Xdebug coverage).
- `phpstan.yml` runs PHPStan on PHP 8.5 (`--error-format=github`).
- `fix-php-code-style-issues.yml` runs Laravel Pint and auto-commits styling fixes.

## Workflows
- If you change PHP support or `composer.json` scripts (`test`, `analyse`, `format`), update workflows to match.
- Install steps in CI currently run `composer kirby`; keep this in sync with how deps are installed for CI.

## Guardrails
- Avoid auto-commit loops (Pint action + auto-commit).
- Keep secrets out of logs; pin action versions.
- Don’t add network-dependent tests or long-running steps.

