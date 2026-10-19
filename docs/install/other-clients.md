# Connect other MCP clients

[All clients](index.md) · [Remote site setup](remote.md) · [HTTP reference](../remote.md)

MCP support alone is not enough to establish compatibility: a client must support the transport, authentication, and tools you intend to use. Use the dedicated [Claude](claude.md), [ChatGPT](chatgpt.md), [Langdock](langdock.md), [Claude Code](claude-code.md), [Codex](codex.md), or [Cursor](cursor.md) guide where available.

## 1. Match the client to a connection method

| Method        | Client requirement                                                          | Kirby preparation                                                                                         |
| ------------- | --------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| Local stdio   | Can launch a command in the project's environment                           | [Install locally](index.md#prepare-a-local-project); set command and `--project` argument                 |
| Remote OAuth  | Streamable HTTP plus OAuth authorization-code flow and dynamic registration | [Install remote endpoint](remote.md); use the MCP URL and complete Kirby login                            |
| Remote bearer | Streamable HTTP plus a configurable Authorization header                    | Operator configures [remote-token mode](../remote.md#remote-bearer-token); send the raw token as `Bearer` |

Use **Streamable HTTP**, not a legacy SSE-only endpoint setting. Kirby MCP can stream SSE responses as part of its HTTP transport; that does not make `/mcp` a separate legacy `/sse` service.

For built-in OAuth, choose automatic/dynamic registration. Clients that insist on published client-ID documents or custom-scheme redirects may not work with this provider: redirects must use HTTPS or loopback HTTP. Do not invent a static client ID/secret, turn off authentication, or put tokens into URLs to work around this.

## 2. Enter the server details

For stdio, use the installed project's absolute `vendor/bin/kirby-mcp` path as the command and `--project=/absolute/project` as an argument. Apply the [PHP/path guidance](index.md#paths-and-operating-systems) when the GUI or remote development environment differs from your terminal.

For HTTP, use the deployed endpoint, normally `https://example.com/mcp`. A cloud-hosted client needs network access from its own servers; it cannot use your laptop's localhost just because your browser can.

The following examples show why configuration formats must be client-specific.

### VS Code

1. Use **MCP: Add Server**, or open the workspace's `.vscode/mcp.json` through **MCP: Open Workspace Folder MCP Configuration**.
2. Add the appropriate server below; you do not need both. VS Code uses `servers`, not `mcpServers`:

   ```json
   {
     "servers": {
       "kirby": {
         "type": "stdio",
         "command": "/absolute/project/vendor/bin/kirby-mcp",
         "args": ["--project=/absolute/project"]
       },
       "kirby-remote": {
         "type": "http",
         "url": "https://example.com/mcp"
       }
     }
   }
   ```

3. Replace the paths/URL, review the server trust prompt, and start the selected server through **MCP: List Servers**. Complete browser authorization for OAuth; leave static client credentials unset for DCR.
4. Enable the relevant tools in chat and run the read check below. Use **MCP: Open User Configuration** instead if the registration should be user-wide.

For a remote-token deployment, add an Authorization header to the HTTP entry using VS Code's documented `${env:KIRBY_MCP_TOKEN}` interpolation or secure password input. See the [configuration reference](https://code.visualstudio.com/docs/agents/reference/mcp-configuration); never commit a literal credential.

### Amp

For a local client with the project available:

1. Complete [local installation](index.md#prepare-a-local-project).
2. Run from the project root:

   ```bash
   amp mcp add kirby -- "$PWD/vendor/bin/kirby-mcp" "--project=$PWD"
   amp mcp doctor
   ```

3. If using a workspace `.amp/settings.json` definition, approve it when prompted. Run the read check below in a thread using that project.

For an account-stored remote OAuth connection, use Amp's remote MCP controls or CLI:

```bash
amp mcp remote --personal add Kirby https://example.com/mcp --auth oauth
amp mcp remote --personal login Kirby
amp mcp remote --personal check Kirby
```

Replace the URL, complete the site login through Amp's provided authorization flow, and check that the connection is available in your thread. This creates a personal remote definition; workspace/project scopes are separate choices. Remote definitions are useful when the project runs in an orb: do not assume an OAuth callback to a local terminal browser is supported there.

For a bearer deployment, follow [Amp's secret-input instructions](https://ampcode.com/docs/customize/mcp). Local settings use `${KIRBY_MCP_TOKEN}` interpolation, not Cursor's `${env:KIRBY_MCP_TOKEN}`. Remotely stored definitions accept private input or token-file input; do not supply a secret as a literal command argument.

## 3. Verify discovery and a tool result

1. Inspect the server status and ensure `kirby_read_page_content` is available to the connected account.
2. Request:

   ```text
   Use the Kirby MCP server to call kirby_init if required, then read page ID
   home with kirby_read_page_content. Report its title without making changes.
   ```

3. Inspect the actual tool output and compare it to the Panel. Use a page ID that exists on your site.
4. If the client supports MCP resources, optionally inspect a permitted resource such as `kirby://kb`. Tool support does not imply that every client exposes resources or subscriptions in its UI.

Keep client-side approval for writes. The server still requires the appropriate scopes, role capabilities, and [MCP confirmation](../reference.md#confirmations-and-resource-links). A client without elicitation support may need an explicit confirmed retry; do not interpret a preview as an executed update.

## 4. Refresh or remove the connection

After package/runtime updates, restart local server processes and refresh remote tool discovery. For authorization failures, consult [remote recovery](remote.md#recover-or-disable-access) before widening permissions.

In VS Code, remove the definition from the workspace/user configuration where it was added. Dynamic authentication registrations can be removed separately with **Authentication: Remove Dynamic Authentication Providers**.

For an Amp personal remote definition, use:

```bash
amp mcp remote --personal logout Kirby
amp mcp remote --personal remove Kirby
```

For a local Amp definition, remove its entry from the applicable user/workspace settings and reload the client. Removing local configuration, clearing stored OAuth credentials, and restricting access on the Kirby server are separate operations.

## Official sources and verification status

Documentation checked **2026-10-19**:

- [VS Code: add and manage MCP servers](https://code.visualstudio.com/docs/agent-customization/mcp-servers)
- [VS Code: configuration reference](https://code.visualstudio.com/docs/agents/reference/mcp-configuration)
- [VS Code: OAuth and dynamic registration](https://code.visualstudio.com/api/extension-guides/ai/mcp)
- [Amp: MCP configuration and remote connections](https://ampcode.com/docs/customize/mcp)

These are client-specific examples of documented interfaces, not a promise of compatibility with every MCP client or an end-to-end sign-in certification. For an unlisted client, record its version, transport, authentication mode, and exact error when reporting an issue; omit credentials and private content.
