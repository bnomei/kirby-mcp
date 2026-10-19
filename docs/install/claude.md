# Connect Claude to Kirby MCP

[All clients](index.md) · [Prepare the remote site](remote.md) · [Claude Code](claude-code.md)

Use this guide for an account-level connector in Claude web or Desktop. A remote connector runs through Anthropic's infrastructure even when you use the Desktop app. For a project that exists only on your computer, use the [local Desktop alternative](#local-desktop-alternative) or [Claude Code](claude-code.md).

## 1. Obtain the endpoint and account

Ask the site operator to complete [remote installation](remote.md). You need:

- The MCP URL, normally `https://example.com/mcp`.
- A Kirby account with the role admitted by the OAuth provider and the required MCP capabilities.
- Permission to add a connector in Claude. Anthropic currently documents custom connectors for Free, Pro, Max, Team, and Enterprise, with one custom connector on Free. Organization setup requires the appropriate owner or administrative role; check the [official requirements](https://support.claude.com/en/articles/11175166-get-started-with-custom-connectors-using-remote-mcp).

The site must be reachable from Anthropic's servers. A VPN connection on your laptop does not give the remote connector access to a private site. Do not use a localhost URL for this flow.

## 2. Register the connector

1. For a personal account, open **Customize → Connectors**, choose **Add**, then **Add custom connector**.
2. For an organization, an authorized administrator instead opens **Organization settings → Connectors → Add → Custom → Web**. After registration, members find the connector under **Customize → Connectors** and connect individually.
3. Enter the site's name and MCP URL, then continue to the detected authentication settings.
4. Configure the following choices for Kirby MCP's built-in provider:

   | Setting         | Selection                                        |
   | --------------- | ------------------------------------------------ |
   | Authentication  | **Sign in now**                                  |
   | OAuth client    | **Register automatically**                       |
   | Request headers | No static bearer credential for this OAuth setup |

5. Finish adding the connector. If registration does not immediately launch authorization, use its **Connect** action.

**Choose automatic registration even if Claude recommends its published identity.** Kirby MCP implements dynamic client registration; it does not resolve client-ID metadata documents. Do not enter a made-up client ID/secret or choose **No sign in** to bypass authentication.

## 3. Authorize with Kirby

1. Check that the browser opened your site's domain.
2. Complete the normal Panel login, including any configured challenge. Use the account the site operator authorized, not automatically your administrator account.
3. Review the client and requested scopes on the consent form, then approve the connection you initiated.
4. Return to Claude and check its connection status.

Kirby login is handled by the site; do not paste your Panel password into a chat. If the login handoff expires, start again from Claude and finish in the same browser session. The default handoff lasts ten minutes.

## 4. Verify the site in a conversation

1. Start a chat. Open the composer's **+ → Connectors** menu and enable this site's connector.
2. Ask:

   ```text
   Use this site's Kirby MCP connector to read the home page with
   kirby_read_page_content. Report its title without changing anything.
   ```

3. Inspect the actual tool result and compare the title with the Panel. Replace `home` with an existing page ID if necessary.

The [recommended remote role](remote.md) exposes a small read-only tool set. Missing template, write, or execution tools are expected under that role.

## 5. Expand access deliberately

Keep approval enabled for tools that change content. Claude's permission controls do not grant missing Kirby permissions or OAuth scopes. Ask the site operator to follow [the write-access procedure](remote.md#add-write-access-only-when-needed), then register/authorize the updated connection and refresh its tools.

Only approve an edit after checking its target and proposed values. A successful confirmed update affects stored content; it does not create a separate publication-review task.

## Troubleshoot and disconnect

| Problem                                              | Action                                                                                                                                  |
| ---------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------- |
| Connector creation is unavailable                    | Check your account entitlement and organization permissions with the administrator.                                                     |
| Site opens in your browser but Claude cannot connect | Check public reachability, firewall rules, HTTPS, and the [provider IP guidance](https://platform.claude.com/docs/en/api/ip-addresses). |
| OAuth fails with an unknown client                   | Recreate the connector using **Register automatically**.                                                                                |
| Kirby login succeeds but authorization fails         | Check admitted role, MCP `access`, and [remote diagnostics](remote.md#recover-or-disable-access).                                       |
| Claude answers without using Kirby                   | Enable the connector for the conversation and explicitly request the tool by name.                                                      |

Remove the connector through **Customize → Connectors**; organization administrators manage shared definitions in organization settings. Client removal is not a substitute for [server-side access restriction](remote.md#recover-or-disable-access).

## Local Desktop alternative

This is a separate stdio connection, not an account-level remote connector. It is available only where the local Desktop client supports local MCP servers.

1. Complete [local installation](index.md#prepare-a-local-project).
2. Open the Desktop application's **Settings → Developer → Edit Config** to edit `claude_desktop_config.json`, as described in the [official MCP local-server guide](https://modelcontextprotocol.io/docs/develop/connect-local-servers). This is separate from Claude's web account settings.
3. Merge this entry into its `mcpServers` object, replacing both paths:

   ```json
   {
     "mcpServers": {
       "kirby": {
         "command": "/absolute/project/vendor/bin/kirby-mcp",
         "args": ["--project=/absolute/project"]
       }
     }
   }
   ```

4. Fully restart Claude Desktop. Check the server status, ask it to call `kirby_init` if required, and run the read check above.

Local stdio does not use OAuth or the remote Kirby role. If startup fails, check [PHP and path handling](index.md#paths-and-operating-systems). Remove the entry and restart Desktop to disconnect it.

## Official sources and verification status

Documentation checked **2026-10-19**:

- [Anthropic: custom remote connectors](https://support.claude.com/en/articles/11175166-get-started-with-custom-connectors-using-remote-mcp)
- [Anthropic: local MCP in Claude Desktop](https://support.claude.com/en/articles/10949351-getting-started-with-local-mcp-servers-on-claude-desktop)
- [MCP: manual local-server configuration in Claude Desktop](https://modelcontextprotocol.io/docs/develop/connect-local-servers)

The client workflow is based on these sources; DCR and permission requirements were checked against Kirby MCP's implementation. This tutorial does not claim a live Claude account sign-in test.
