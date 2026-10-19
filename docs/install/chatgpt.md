# Connect ChatGPT to Kirby MCP

[All clients](index.md) · [Prepare the remote site](remote.md) · [Codex CLI](codex.md)

Connect a hosted Kirby project as an OAuth-protected MCP app in ChatGPT on the web. This is not a Codex CLI registration or an OpenAI API integration. ChatGPT cannot launch your project's local PHP executable.

## 1. Confirm that your account can create an MCP app

Ask the site operator for the HTTPS MCP endpoint and an authorized Kirby account after completing [remote installation](remote.md). The standard URL is `https://example.com/mcp`.

Check the controls available in your ChatGPT account before changing the site. OpenAI's official pages currently describe different availability and navigation:

| Official source                                                                                                    | Published setup path                                                                                                                                         |
| ------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| [Developer-mode guide](https://developers.openai.com/api/docs/guides/developer-mode)                               | Lists Plus, Pro, Business, Enterprise, and Education; describes **Settings → Security and login → Developer mode**, followed by app creation in **Plugins**. |
| [Workspace/help-center guide](https://help.openai.com/en/articles/12584461-developer-mode-and-mcp-apps-in-chatgpt) | Describes full MCP for Business and Enterprise/Edu, more limited Pro access, and **Apps → Advanced Settings** / workspace administrator controls.            |

These are not interchangeable guarantees of entitlement. Use the official route matching your account's interface; if neither offers custom MCP creation, consult your workspace administrator or OpenAI support. Do not assume that upgrading to a particular plan will resolve it based solely on this tutorial.

## 2. Create an app for the site's endpoint

1. Enable developer mode using the applicable path above. In a managed workspace, an administrator may first need to grant access under **Permissions & Roles → Connected Data**.
2. Open **Plugins** and its add/create control if your account uses the developer-mode interface. In the documented workspace interface, open **Settings → Apps → Create**, or **Workspace settings → Apps → Create** as an administrator.
3. Supply these values in the app form:

   | Field                                 | Value                                                  |
   | ------------------------------------- | ------------------------------------------------------ |
   | Name                                  | A recognizable site label, such as `Kirby — Staging`   |
   | MCP server URL / connection           | `https://example.com/mcp`, replaced with your endpoint |
   | Authentication                        | **OAuth**                                              |
   | OAuth client registration, if offered | **Dynamic client registration / DCR**                  |
   | Static client ID and secret           | Leave unset for automatic registration                 |

4. Complete the trust acknowledgement only after checking the domain. If the interface offers **Scan Tools**, run it and complete the OAuth prompt before finishing **Create**. Otherwise continue through creation into the authorization flow.

Do not select unauthenticated or mixed-authentication mode: Kirby MCP requires bearer authentication for initialization and tool discovery as well as tool calls. Do not select published client identity/CIMD for the built-in provider. If the interface requires a static client without offering DCR, stop and resolve the client/provider compatibility rather than inventing credentials.

## 3. Complete the site's login

1. Verify the site's hostname in the authorization window.
2. Log in through Kirby's Panel with the account admitted by the site's OAuth configuration.
3. Approve the expected client and read/runtime scopes.
4. Return to ChatGPT and let its tool discovery finish. The app may initially be marked as a developer app or draft.

The login/consent step authorizes a connection; it does not publish a workspace app. If other members need access, an authorized administrator must review and publish it using [OpenAI's workspace procedure](https://help.openai.com/en/articles/12584461-developer-mode-and-mcp-apps-in-chatgpt).

## 4. Exercise a read-only tool

1. Open a new chat and select your app. Depending on the interface, use the **+ → Developer mode** picker, the tools menu, or an `@` mention of the app.
2. Send this request:

   ```text
   Use the Kirby — Staging app and its kirby_read_page_content tool to read
   page ID home. Return the title. Do not browse the website or change content.
   ```

3. Expand the tool result and compare its content with the Panel. Use your actual app label and an existing page ID.
4. Select or mention the app again when a later message needs another tool call; do not assume selection persists across all messages or modes.

No generic `search`/`fetch` adapter is needed for the developer-mode tool flow. Separate features such as company knowledge or deep research have their own requirements; this guide does not claim support for them.

## 5. Manage permissions and updates

Start with the [read-only server role](remote.md). If you later need edits, have the operator [grant the required scopes and capabilities](remote.md#add-write-access-only-when-needed), recreate/authorize the connection as needed, and review the app's enabled actions. Client approvals and Kirby MCP confirmations are separate; neither bypasses missing server permissions.

ChatGPT can retain a snapshot of a workspace app's tools. After a Kirby MCP update or capability change, refresh its tools/actions in the app settings and have an administrator review/publish the update where required. Reconnecting alone may not refresh the approved tool schema.

## Troubleshoot and disconnect

| Problem                                | Action                                                                                                                |
| -------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| No developer-mode or create control    | Check the two official account/workspace guides and administrator policy; this is not fixed by changing Kirby routes. |
| Tool scanning fails                    | Check remote discovery, HTTPS reachability, OAuth DCR, and the account's MCP permissions.                             |
| `invalid_scope` during OAuth           | Use only the scopes the provider advertises. Do not automatically add `openid` or `offline_access`.                   |
| Authentication expires repeatedly      | Check whether the client obtained and renewed refresh tokens; see the note below.                                     |
| App connects but a changed tool errors | Refresh the app's tool snapshot and review/publish the changes.                                                       |
| An edit is unavailable                 | Check account support, enabled app actions, token scopes, and Kirby capabilities before retrying.                     |

OpenAI's help center discusses `offline_access` for some OAuth/OIDC providers. Kirby MCP's built-in provider issues refresh tokens but advertises its own `kirby-mcp:*` scopes, not `offline_access`. Do not add an unsupported scope to work around a client error. Refresh-token interoperability with ChatGPT still needs to be checked on your deployment.

Disconnect or remove the app through its ChatGPT settings. Workspace administrators control shared publication/access. For server-side denial or compromised credentials, follow [remote access recovery](remote.md#recover-or-disable-access); removing a client entry alone is not server-side revocation.

## Official sources and verification status

Documentation checked **2026-10-19**:

- [OpenAI: ChatGPT developer mode](https://developers.openai.com/api/docs/guides/developer-mode)
- [OpenAI: developer mode and MCP apps, including workspace deployment](https://help.openai.com/en/articles/12584461-developer-mode-and-mcp-apps-in-chatgpt)

The account/UI discrepancy above remains unresolved in the official sources. This guide documents the available paths and Kirby MCP's requirements; it does not certify ChatGPT login, token refresh, or write behavior against a live site.
