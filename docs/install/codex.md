# Connect Codex CLI to Kirby MCP

[All clients](index.md) · [Remote site setup](remote.md) · [ChatGPT](chatgpt.md)

Codex CLI can launch a local Kirby MCP process or connect to a site's HTTP endpoint. This guide configures Codex, not an MCP app in ChatGPT.

## 1. Prepare the client and project

1. Install/sign in to Codex CLI using [OpenAI's CLI guide](https://developers.openai.com/codex/cli).
2. For local access, complete [local installation](index.md#prepare-a-local-project).
3. For remote access, obtain the URL and account from the operator who completed [remote installation](remote.md).

Use a site-specific name such as `kirby-example` if you work with several projects. CLI registrations use the user configuration; a generic name can otherwise keep pointing to the wrong site.

## 2A. Register a local process

Run from your project's root in a macOS/Linux shell:

```bash
codex mcp add kirby -- "$PWD/vendor/bin/kirby-mcp" "--project=$PWD"
codex mcp get kirby
```

For a project-scoped definition instead, merge this into `.codex/config.toml`, replacing both paths:

```toml
[mcp_servers.kirby]
command = "/absolute/project/vendor/bin/kirby-mcp"
args = ["--project=/absolute/project"]
```

Project configuration loads only for trusted projects. The user-wide file is `~/.codex/config.toml`. Choose the intended location rather than keeping duplicate definitions. Codex launches the process; do not start a separate HTTP service for stdio.

## 2B. Register a remote OAuth endpoint

```bash
codex mcp add kirby-remote --url https://example.com/mcp
codex mcp login kirby-remote
```

Replace the URL, follow the browser to the site's Panel login, and approve the connection as the authorized Kirby user. Codex documents dynamic client registration support, which matches Kirby MCP's built-in provider. No static client ID or secret is needed for that flow.

An equivalent URL definition, before running `codex mcp login kirby-remote`, is:

```toml
[mcp_servers.kirby-remote]
url = "https://example.com/mcp"
```

## 2C. Use a bearer credential instead

This alternative requires the operator to configure [remote-token mode](../remote.md#remote-bearer-token), not the OAuth setup above. Have your secret manager or launch environment supply the raw token as `KIRBY_MCP_TOKEN`; do not put its value into shell history or committed configuration.

```bash
codex mcp add kirby-remote --url https://example.com/mcp --bearer-token-env-var KIRBY_MCP_TOKEN
```

Or use this TOML entry instead of the OAuth definition:

```toml
[mcp_servers.kirby-remote]
url = "https://example.com/mcp"
bearer_token_env_var = "KIRBY_MCP_TOKEN"
```

The setting names an environment variable, not a secret value. Codex adds the `Bearer` prefix. The variable must exist in the process launching Codex; another terminal or an already-running IDE may not inherit it. Do not run OAuth login for this alternative.

## 3. Verify a tool call

1. Run `codex mcp list`, start Codex, and use `/mcp` to check active servers.
2. Send:

   ```text
   Use the configured Kirby MCP server, not the shell, to initialize if needed
   and read page ID home with kirby_read_page_content. Report the title only;
   do not modify files or content.
   ```

3. Inspect the tool output and compare the title with the Panel. Substitute a real page ID or explicitly name the server if more than one is configured.

The restricted remote role exposes fewer tools than local stdio. A successful connection with no write tools is expected for the starter configuration.

## 4. Update, expand access, or remove

Restart the Codex session after configuration changes and [runtime updates](index.md#keep-installations-current). For remote editing, follow [the write-access procedure](remote.md#add-write-access-only-when-needed) rather than disabling confirmations. Codex's other tools can act outside MCP's permission boundary.

For an OAuth connection, clear locally stored credentials and then remove the registration:

```bash
codex mcp logout kirby-remote
codex mcp remove kirby-remote
```

For a local server, use `codex mcp remove kirby`; if you added it manually to project TOML, remove that entry there. Bearer credentials must also be removed/rotated at the server when revocation is needed.

## Troubleshooting

| Problem                                   | Action                                                                                                                                                                                        |
| ----------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Wrong project appears                     | Check `codex mcp get`, the absolute `--project` value, user/project configuration, and server naming.                                                                                         |
| Process cannot start                      | Check the [PHP/path requirements](index.md#paths-and-operating-systems) in Codex's execution environment.                                                                                     |
| Bearer request is unauthorized            | Check the environment variable is available, the server is in remote-token mode, and the token maps to an existing user.                                                                      |
| OAuth or permissions fail                 | Follow [remote diagnostics](remote.md#recover-or-disable-access).                                                                                                                             |
| Resource discovery reports a schema error | Record the client version and exact error; see [discovery troubleshooting](../global-reference.md#troubleshoot-startup-and-discovery-separately). This is distinct from a missing executable. |

## Official sources and verification status

Documentation checked **2026-10-19**:

- [OpenAI: Codex MCP configuration and OAuth support](https://developers.openai.com/codex/extend/mcp)
- [OpenAI: Codex CLI command reference](https://developers.openai.com/codex/cli/reference)

These examples follow the documented configuration formats. They do not certify a live Codex login or remote tool call against your deployment.
