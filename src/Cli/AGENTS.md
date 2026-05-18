# CLI Integration Guidelines

## Mission

Provide safe, testable Kirby CLI execution and output parsing for MCP tools and resources.

## System

- `KirbyCliRunner` executes Kirby CLI commands via Symfony Process and returns `KirbyCliResult` (stdout/stderr/exit code).
- `KirbyCliHelpParser` normalizes `kirby help` output; `McpMarkedJsonExtractor` extracts MCP-marked JSON blocks.
- The `bin/kirby-mcp` entrypoint runs the MCP stdio transport by default; use `RunnerControl` and SIGINT/SIGTERM handlers for graceful shutdown when adjusting the run loop.
- Optional HTTP transport must remain explicitly enabled and isolated from stdio output. The default `vendor/bin/kirby-mcp` path must keep clean MCP-only stdout and must not open a network listener.
- HTTP mode serves the single MCP endpoint at `/mcp`, defaults to `127.0.0.1`, requires Bearer auth before MCP handling, and must reject query-string credentials.
- `kirby-cli-prepend.php` exists to avoid global helper collisions when bootstrapping Kirby in CLI contexts.

## Workflows

- When adding a new CLI interaction:
  1. Build an argument array (no shell strings).
  2. Pass required env/context (project root, host) explicitly.
  3. Parse output into stable DTOs/arrays and cover parsers with unit tests.
  4. Add an integration test if the change depends on a real Kirby runtime.

## Guardrails

- Never interpolate untrusted input into a shell command; treat paths/args as data.
- Always capture and return stdout, stderr, and exit codes; don’t silently swallow failures.
- Treat non-zero exit codes or timeouts as command failures; skip JSON parsing and surface stderr in parse errors.
- Keep output formats stable (especially MCP JSON markers).
- When changing transport selection, cover both default stdio behavior and explicit HTTP entrypoint behavior in focused tests.
