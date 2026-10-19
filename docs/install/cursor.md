# Connect Cursor to Kirby MCP

[All clients](index.md) · [Remote site setup](remote.md) · [Configuration](../configuration.md)

Use a project-local configuration to attach Cursor to the project's PHP process, or a remote URL to reach an installed Kirby site. Keep these registrations distinct when both are present.

## 1. Choose the configuration location

1. Install/update Cursor from its [official site](https://cursor.com/download).
2. For local access, complete [local installation](index.md#prepare-a-local-project). For remote access, obtain the endpoint from the operator who completed [remote setup](remote.md).
3. Open the project in Cursor and use `.cursor/mcp.json` for this workspace. Use `~/.cursor/mcp.json` only if you want the registration across projects.

Merge entries into an existing `mcpServers` object; do not replace other servers. Select one of the following connection methods.

## 2A. Configure the local PHP process

```json
{
  "mcpServers": {
    "kirby": {
      "type": "stdio",
      "command": "${workspaceFolder}/vendor/bin/kirby-mcp",
      "args": ["--project=${workspaceFolder}"]
    }
  }
}
```

Cursor resolves `${workspaceFolder}` to the project root containing `.cursor/mcp.json`. For a global entry, use explicit absolute paths to the intended project instead. Cursor starts the MCP process itself; do not run a separate listener.

If PHP cannot be found or the executable cannot launch, use an explicit PHP interpreter as explained in [path handling](index.md#paths-and-operating-systems). A remote development container needs PHP and the project inside that execution environment.

## 2B. Configure remote OAuth

```json
{
  "mcpServers": {
    "kirby-remote": {
      "url": "https://example.com/mcp"
    }
  }
}
```

1. Replace the URL and save the file.
2. Open Cursor's **Customize** MCP controls, enable the server if necessary, and follow the offered authentication action.
3. Complete the site's Panel login and OAuth consent, then return to Cursor.

The built-in provider uses dynamic registration, so omit static OAuth client credentials. It accepts HTTPS and loopback HTTP callback URLs, not custom application schemes. If authentication reports an invalid redirect URI, inspect the actual callback and check your Cursor version; do not weaken the server's redirect validation.

## 2C. Configure a remote bearer token

Use this entry instead of the OAuth entry only when the site operator chose [remote-token mode](../remote.md#remote-bearer-token):

```json
{
  "mcpServers": {
    "kirby-remote": {
      "url": "https://example.com/mcp",
      "headers": {
        "Authorization": "Bearer ${env:KIRBY_MCP_TOKEN}"
      }
    }
  }
}
```

Supply `KIRBY_MCP_TOKEN` through a trusted launch environment or secret manager. The raw token must be available to Cursor, not just to an unrelated terminal. Restart Cursor after changing its inherited environment. Cursor's `envFile` setting is for stdio, not remote HTTP entries; do not rely on it to load this header's token. Never commit the resolved header value.

## 3. Check the connection in Agent

1. Check the server's status/tool list in Customize, then open Agent in the project.
2. Ask:

   ```text
   Use Kirby MCP to initialize if needed and read the home page with
   kirby_read_page_content. Report the stored title. Do not use shell or
   file tools and do not change the site.
   ```

3. Confirm a tool call actually ran and compare its result with the Panel. Use the correct page ID and server name for your project.

Remote tools are restricted by the connected Kirby user's capabilities. For an expanded grant, follow [remote write setup](remote.md#add-write-access-only-when-needed), reconnect with the appropriate authorization, and refresh discovery. Keep approval enabled for mutations; Cursor's non-MCP file editing remains a separate capability.

## Troubleshoot, update, and remove

| Problem                                          | Action                                                                                                          |
| ------------------------------------------------ | --------------------------------------------------------------------------------------------------------------- |
| Local executable cannot start                    | Check PHP availability, file permissions, workspace path resolution, and local versus remote execution context. |
| Server appears twice or points at the wrong site | Inspect both project and global `mcp.json` files.                                                               |
| OAuth callback fails                             | Check DCR support and the actual redirect URI against the server's HTTPS/loopback requirements.                 |
| Bearer token is missing                          | Verify Cursor inherited the variable; `envFile` does not apply to this remote entry.                            |
| Tools are stale                                  | After updating package/runtime files, reload/restart Cursor and inspect discovery again.                        |

Remove the entry from the configuration file where you added it, or remove it through Customize, then reload the client. Disabling a server only pauses its use. Coordinate [server-side revocation](remote.md#recover-or-disable-access) separately when credentials should no longer work.

## Official sources and verification status

Documentation checked **2026-10-19**:

- [Cursor: MCP configuration, scopes, OAuth, and variable interpolation](https://cursor.com/docs/mcp)

The examples use Cursor's documented syntax. Live OAuth callback handling and end-to-end Kirby tool execution were not tested as part of this tutorial.
