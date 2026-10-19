# Connect Langdock to Kirby MCP

[All clients](index.md) · [Prepare the remote site](remote.md) · [HTTP reference](../remote.md)

Use a remote MCP integration to make a Kirby site's permitted tools available in Langdock. The recommended path uses individual Kirby logins through OAuth; a fixed bearer credential is an alternative for deployments deliberately configured that way.

## 1. Check workspace and site access

1. Ask a workspace administrator for permission to create integrations, or ask them to create the definition for you.
2. Have the site operator complete [remote installation](remote.md).
3. Obtain the HTTPS MCP URL and an authorized Kirby account. Use the MCP endpoint, normally `https://example.com/mcp`, not the Panel or an OAuth subroute.

Langdock's hosted service must reach the site. Its URL validation blocks private/internal addresses by default. Dedicated deployments have separate network configuration; consult Langdock's documentation rather than exposing a private site or disabling validation casually.

## 2. Create the integration definition

1. Open **Integrations** in Langdock.
2. Use **Add integration → Start from scratch → Connect remote MCP**.
3. Enter the following connection settings:

   | Setting                 | Value                                       |
   | ----------------------- | ------------------------------------------- |
   | Server URL              | Your site's `https://example.com/mcp`       |
   | Authentication method   | **OAuth 2.0 (Dynamic Client Registration)** |
   | Transport, if requested | **Streamable HTTP**                         |

4. Continue with **Create and connect**; if the integration opens without a login prompt, choose **Add connection**.

Automatic registration uses the metadata published by Kirby MCP. The manual OAuth option is not needed for the built-in provider.

## 3. Authorize and test the connection

1. In the browser authorization window, verify your site's domain and complete the Panel login.
2. Approve the connection and expected read/runtime scopes.
3. Return to the integration and choose **Test connection**. If your interface shows **List Server Features**, use it to retrieve the tool list.
4. Confirm that `kirby_read_page_content` is present for the account configured by our remote walkthrough.

If login succeeds but discovery is empty or forbidden, check the user's role capabilities. A successful OAuth exchange alone does not grant every Kirby MCP tool.

## 4. Select tools and save execution settings

1. Select the tools needed for your task. Start with page reads and, if useful, `kirby_search` for bundled documentation.
2. Save the selection. Langdock does not make every discovered tool available automatically.
3. Review confirmation settings after saving. Langdock documents that newly saved tools/resources do **not** require confirmation by default. Enable **Ask for confirmation** for any tool that can change content or execute code before using it.

Langdock currently documents a limit of 60 enabled tools and 50 resources per integration. Do not select every reference resource just to test a single page read. Role filtering may also make your available list smaller than the Kirby MCP inventory.

## 5. Verify a result in chat

1. In a new conversation, use `@` to select the integration by its saved name.
2. Ask:

   ```text
   With the selected Kirby integration, call kirby_read_page_content for home.
   Tell me the stored title and leave the page unchanged.
   ```

3. Inspect the executed action and returned content. Compare the title with the Panel, substituting another existing page ID if necessary.

For later content edits, ask the operator to follow [the write-access procedure](remote.md#add-write-access-only-when-needed). Register/authorize the expanded grant, retrieve the tools again, save the required actions, and retain per-tool confirmation. Do not assume a reauthorization button alone expands a previously registered OAuth scope grant.

## Bearer-token alternative

Use this only if the operator configured [remote-token authentication](../remote.md#remote-bearer-token) instead of OAuth. The server chooses one authentication mode; an arbitrary static token will not authenticate to the recommended OAuth setup.

1. Select **API Key** as the integration authentication method.
2. Enter the operator-provided raw token in Langdock's credential field, not in the server URL or chat.
3. Set **API Key Header Type** to **Authorization: Bearer**. Do not use the default legacy header combination or `X-API-Key`.
4. Test the connection, select tools, save, and run the same read check.

Everyone using a shared credential reaches Kirby as the user bound to that token. Check Langdock's connection-sharing settings before making the integration available to a team. Use separate credentials or individual OAuth connections when identities must remain separate.

## Troubleshoot, refresh, and disconnect

| Problem                                  | Action                                                                                                        |
| ---------------------------------------- | ------------------------------------------------------------------------------------------------------------- |
| Cannot add an integration                | Ask for the workspace's create-integrations permission.                                                       |
| URL rejected                             | Check public HTTPS reachability and Langdock's private-address restrictions.                                  |
| Test succeeds but chat cannot use a tool | Select the tool, save the integration, and attach it to the conversation.                                     |
| New tools are missing after an update    | Retrieve the server features again, review the selection and confirmations, then save.                        |
| Read works but update fails              | Check scopes, the complete Kirby capability chain, native mutation permissions, and MCP confirmation.         |
| API key rejected                         | Confirm server mode, raw token versus hash, existing bound user, and **Authorization: Bearer** header format. |

Disconnect/remove the saved connection in the integration's settings, coordinating with the workspace owner if it is shared. Ask the operator to rotate/remove bearer credentials or [restrict server access](remote.md#recover-or-disable-access) when needed. Removing the Langdock definition alone does not revoke every credential on the server.

## Official sources and verification status

Documentation checked **2026-10-19**:

- [Langdock: MCP transports, authentication, tool selection, and limits](https://docs.langdock.com/en/using-langdock/guides/integrations/mcp/mcp)
- [Langdock: integration creation and workspace permission example](https://docs.langdock.com/en/using-langdock/guides/integrations/mcp/langdock-agent-mcp-server)
- [Langdock: selecting integrations in chat](https://docs.langdock.com/en/using-langdock/chat/tools/actions-in-chat)

These instructions adapt Langdock's documented client capabilities to Kirby MCP's endpoint and permission model. No live Langdock workspace connection or tool execution is certified by this guide.
