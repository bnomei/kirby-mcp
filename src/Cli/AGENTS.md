# CLI Integration Guidelines

## Mission
Provide safe, testable Kirby CLI execution and output parsing for MCP tools and resources.

## System
- `KirbyCliRunner` executes Kirby CLI commands via Symfony Process and returns `KirbyCliResult` (stdout/stderr/exit code).
- `KirbyCliHelpParser` normalizes `kirby help` output; `McpMarkedJsonExtractor` extracts MCP-marked JSON blocks.
- `kirby-cli-prepend.php` exists to avoid global helper collisions when bootstrapping Kirby in CLI contexts.

## Workflows
- When adding a new CLI interaction:
  1) Build an argument array (no shell strings).
  2) Pass required env/context (project root, host) explicitly.
  3) Parse output into stable DTOs/arrays and cover parsers with unit tests.
  4) Add an integration test if the change depends on a real Kirby runtime.

## Guardrails
- Never interpolate untrusted input into a shell command; treat paths/args as data.
- Always capture and return stdout, stderr, and exit codes; don’t silently swallow failures.
- Keep output formats stable (especially MCP JSON markers).

