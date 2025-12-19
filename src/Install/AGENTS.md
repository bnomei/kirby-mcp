# Runtime Installation Guidelines

## Mission
Install/update Kirby runtime command wrappers into a host project without surprising edits.

## System
- `RuntimeCommandsInstaller` copies this package’s `commands/` directory into the host commands root
  (default `site/commands`, or discovered via `KirbyRootsInspector`).
- Installed files should stay thin proxies to `Bnomei\\KirbyMcp\\Mcp\\Commands\\*::definition()`.

## Workflows
- When adding/removing a runtime command, update both:
  - `src/Mcp/Commands/<Command>.php`
  - `commands/mcp/...` template path (maps to the `mcp:*` CLI command name)
- Verify install/update behavior with integration tests (install + command availability).

## Guardrails
- Keep installs idempotent: respect `force=false` and only overwrite when explicitly requested.
- Only write inside the resolved commands root; don’t touch unrelated project files from this layer.
- Templates must be safe to copy verbatim (no absolute paths, no env-specific logic).

