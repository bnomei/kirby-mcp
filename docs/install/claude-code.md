# Connect Claude Code to Kirby MCP

[All clients](index.md) · [Remote site setup](remote.md) · [Claude web/Desktop](claude.md)

Choose local stdio for a project in Claude Code's execution environment, or HTTP for a deployed site. The commands below create an explicit Claude Code registration; they do not require a Claude web connector.

## 1. Prepare the connection

- Install Claude Code using its [official setup instructions](https://code.claude.com/docs/en/setup).
- For local access, complete [local Kirby MCP installation](index.md#prepare-a-local-project), then open a terminal at the project root.
- For remote access, obtain the endpoint and account from the operator who completed [remote setup](remote.md).

Choose one of the next two registration methods. The examples use different names so you can distinguish local and deployed projects if you intentionally keep both.

## 2A. Register the local project

From the project root in a macOS/Linux shell:

```bash
claude mcp add --scope local kirby -- "$PWD/vendor/bin/kirby-mcp" "--project=$PWD"
claude mcp get kirby
```

`local` scope means a private registration for the current project. `project` scope instead writes a shared `.mcp.json`; `user` scope makes a registration available across projects. Avoid making a project-specific server user-wide unless that is intentional.

The command after `--` runs as a stdio process. Claude Code launches it; no separate server terminal or HTTP setup is required. Absolute paths keep it attached to this project when the working directory changes. See [path guidance](index.md#paths-and-operating-systems) for other environments.

## 2B. Register the remote site

From the project directory where you want the registration:

```bash
claude mcp add --scope local --transport http kirby-remote https://example.com/mcp
claude mcp get kirby-remote
```

Replace the URL with the operator's endpoint. Then:

1. Start `claude` in that directory.
2. Run `/mcp`, select `kirby-remote`, and choose **Authenticate**.
3. Complete the browser's Kirby login and approve the expected connection.
4. Return to `/mcp` and confirm that the server is available.

Kirby MCP's built-in provider uses dynamic client registration. If your client version offers published identity versus automatic registration, use automatic registration. Do not add a static bearer header to this OAuth entry. Public remote sites require HTTPS; prefer stdio for local development rather than configuring public shared-token access.

## 3. Check a real read

1. Use `/mcp` to inspect the server and its tools.
2. Ask Claude Code:

   ```text
   Use Kirby MCP, not shell commands, to read the home page title. Call
   kirby_init first if required, then kirby_read_page_content. Make no changes.
   ```

3. Inspect the returned page content and compare it with the Panel. Replace `home` if needed and name `kirby` or `kirby-remote` explicitly if both are configured.

Remote tools reflect the Kirby account's role. The starter role intentionally lacks most project-inspection and mutation tools; local stdio is trusted-operator access and has a broader surface.

## 4. Maintain or remove the registration

After [package/runtime updates](index.md#keep-installations-current), start a fresh Claude Code session and recheck discovery. For remote writes, follow [the server-side access procedure](remote.md#add-write-access-only-when-needed) and retain approval for mutations. Claude Code's shell/file access is independent of MCP confirmations.

Inspect registrations with `claude mcp list`. Remove the chosen entry with:

```bash
claude mcp remove kirby --scope local
claude mcp remove kirby-remote --scope local
```

Use the scope where you originally registered it. Client removal is not [server-side credential revocation](remote.md#recover-or-disable-access).

## Troubleshooting

| Problem                                           | Action                                                                                                           |
| ------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------- |
| Executable not found                              | Inspect `claude mcp get kirby`; check absolute paths, executable permissions, and PHP in the client environment. |
| Shared project server does not start              | Approve the project-scoped `.mcp.json` configuration and start a new session.                                    |
| Registered server is missing in another directory | Check registration scope; local scope belongs to the original project.                                           |
| OAuth login or a remote tool fails                | Use the [remote recovery checklist](remote.md#recover-or-disable-access).                                        |
| Agent uses shell instead of MCP                   | Specify the server and read tool explicitly; a shell result does not verify the connection.                      |

## Official sources and verification status

Documentation checked **2026-10-19**:

- [Claude Code: MCP quickstart, registration scopes, authentication, and removal](https://code.claude.com/docs/en/mcp-quickstart)
- [Claude Code: setup](https://code.claude.com/docs/en/setup)

Commands follow the documented CLI interface. This guide does not claim a live Claude Code OAuth session was tested against Kirby MCP.
