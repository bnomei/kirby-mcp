# Installation tutorial guidelines

- Write original, task-oriented instructions from Kirby MCP's implementation and official client documentation. Do not adapt third-party tutorial prose, screenshots, product features, or navigation structure.
- Organize client tutorials around prerequisites, connection setup, authorization, a read-only success check, optional capabilities, and recovery/removal. Use numbered actions and field/value tables where they help a reader complete the task without screenshots.
- Keep shared local prerequisites in `index.md`, the recommended remote setup in `remote.md`, and advanced HTTP/security contracts in `../remote.md`. Link between them rather than duplicating all configuration in each client page.
- Include official source links and a documentation-check date on client pages. Distinguish vendor-documented functionality and source-checked server behavior from an end-to-end integration test. Record conflicting vendor guidance rather than silently choosing a favorable claim.
- Use the actual `/mcp` endpoint and built-in OAuth dynamic client registration. Do not imply support for client-ID metadata documents, an MCP connection-management Panel view, deferred publication, or automatic scope escalation.
- Start remote walkthroughs with read/runtime scopes and a restricted Kirby role. Explain that scopes, role capabilities, client approvals, and MCP confirmations are different controls. Permission filtering is not per-page isolation.
- Never put real credentials in examples, URLs, committed client settings, or command arguments. Bearer alternatives require the server's `remote-token` mode; they are not interchangeable with the recommended OAuth deployment.
- Keep the README a short entry point. Validate Markdown links and JSON/TOML/YAML/PHP examples after editing; do not describe static documentation checks as successful client sign-ins.
