# Install Kirby MCP for your client

[README](../../README.md) · [HTTP reference](../remote.md) · [Configuration](../configuration.md)

Start with where your Kirby project runs. A coding client can launch a local process; a cloud assistant needs a reachable HTTP endpoint. Installing the Composer package alone does not connect either kind of client.

## Choose a connection

| Your situation                                                    | Start here                                                                                            |
| ----------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------- |
| A hosted Kirby site that people will access through AI assistants | Ask the site operator to complete [remote installation](remote.md), then follow a client guide below. |
| A project on the same machine as your coding client               | Complete [local installation](#prepare-a-local-project), then register it in your client.             |
| Kirby research without a project                                  | Use [global reference mode](../global-reference.md); it cannot read or change a site's content.       |

| Client tutorial                                              | Connection covered                                              |
| ------------------------------------------------------------ | --------------------------------------------------------------- |
| [Claude web and Desktop](claude.md)                          | Remote OAuth connector; local Desktop alternative               |
| [ChatGPT](chatgpt.md)                                        | Remote OAuth MCP app                                            |
| [Langdock](langdock.md)                                      | Remote OAuth integration; bearer alternative                    |
| [Claude Code](claude-code.md)                                | Local stdio or remote OAuth                                     |
| [Codex CLI](codex.md)                                        | Local stdio, remote OAuth, or remote bearer                     |
| [Cursor](cursor.md)                                          | Local stdio, remote OAuth, or remote bearer                     |
| [Other clients, including VS Code and Amp](other-clients.md) | Transport/authentication checklist and client-specific examples |

These tutorials describe vendor-documented setup and Kirby MCP's server contracts. They are not an integration certification. Client interfaces, account entitlements, OAuth behavior, and tool support can vary by version. Each client page links to its official sources and records when they were checked; successful authentication and a real tool result are the final checks on your installation.

## Prepare a local project

1. Use a Composer-based Kirby 6 project with PHP 8.3+ and an executable PHP CLI. Check `php --version` and `composer --version` in the environment where your client will run.
2. Make a backup or work on a development copy. The verified CMS baseline is **Kirby 6.0.0-beta.1**; Kirby MCP's stable package does not make the CMS beta production-ready. See the [README's version guidance](../../README.md#quickstart). Kirby 5 requires the older `^1.15` package line, not these version-6 instructions.
3. From the directory containing the project's `composer.json`, run:

   ```bash
   composer require --dev bnomei/kirby-mcp:"^6.0"
   vendor/bin/kirby-mcp install
   ```

4. Confirm that `.kirby-mcp/mcp.json`, the runtime commands, and the plugin adapter were installed. Default runtime locations are `site/commands/mcp/` and `site/plugins/kirby-mcp/`; custom Kirby roots are respected.
5. Follow your client's tutorial. The client launches the stdio process when needed. Do not start an HTTP listener for this path.

Local stdio is trusted access to the project, not a Kirby-user permission boundary. Your coding client may also have filesystem or shell tools outside MCP's controls.

### Paths and operating systems

Shell examples using `$PWD` target macOS/Linux shells, including a client running inside WSL. Substitute actual absolute paths in JSON/TOML examples. A path must exist on the machine or container that launches the process, not merely on the computer displaying the editor.

If a GUI cannot find PHP, configure the absolute PHP executable as `command`, put the MCP script first in `args`, and keep `--project` as a separate argument. For native Windows, this shape avoids depending on Unix executable handling:

```json
{
  "command": "C:/php/php.exe",
  "args": ["C:/Sites/example/vendor/bin/kirby-mcp", "--project=C:/Sites/example"]
}
```

This is a server-entry fragment; place it inside your client's configuration structure. Replace every path, and do not mix Windows and WSL paths. Native Windows execution is not verified by these tutorials.

## Check the connection before asking for changes

Use the same read-only check after registration in any client:

```text
Use the Kirby MCP connection for this site. Call kirby_read_page_content for
the page ID home and report its title. Do not update content or use other tools.
```

Replace `home` if your site uses a different home page ID. For local stdio, ask the client to call `kirby_init` first if required. The restricted remote role in our setup guide also permits `kirby_init`.

Success means you can inspect a tool result containing the requested page's content, and its title agrees with the Panel. A plausible answer without a tool call is not a connection test. If the page is missing, use an existing page ID rather than changing permissions at random.

## Keep installations current

After updating the Composer package, refresh the installed runtime files:

```bash
composer update bnomei/kirby-mcp
vendor/bin/kirby-mcp update
```

Then restart local clients or refresh remote tool lists as described in the relevant tutorial. Keep credentials and `.kirby-mcp/` private. See [maintenance](../../README.md#maintenance) for removal and [remote recovery](remote.md#recover-or-disable-access) for server-side access controls.
