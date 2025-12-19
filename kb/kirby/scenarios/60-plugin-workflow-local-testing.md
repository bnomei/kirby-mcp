# Scenario: Plugin workflow (local testing + fixtures)

## Goal
Set up a productive plugin development workflow that supports:
- local testing against a Kirby installation
- running plugin tests in isolation
- keeping fixtures/content manageable

## Inputs to ask for
- Where the plugin lives (inside a Kirby project vs separate repo)
- Whether tests exist (phpunit/pest)
- Whether Panel assets need building

## Internal tools/resources to use
- Determine available test commands: `kirby://composer`
- Inspect project structure: `kirby://roots`

## Implementation steps
1. Ensure the plugin can boot in a minimal Kirby instance (test bootstrap).
2. Keep fixtures small and deterministic.
3. Automate with composer scripts where possible.

## Examples
- Keep a `tests/bootstrap.php` that boots Kirby with minimal roots/fixtures.
- Run tests via `composer test` (prefer repo-local scripts).

## Verification
- Run the plugin test suite locally.
- Boot a minimal Kirby instance with the plugin enabled.

## Glossary quick refs

- kirby://glossary/plugin
- kirby://glossary/roots

## Links
- Cookbook: Plugin workflow: https://getkirby.com/docs/cookbook/plugins/plugin-workflow
- Guide: Plugins: https://getkirby.com/docs/guide/plugins
