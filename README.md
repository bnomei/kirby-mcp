# Kirby MCP

[![Kirby 6](https://flat.badgen.net/badge/Kirby/6?color=ECC748)](https://getkirby.com/releases/6)
![PHP 8.3](https://flat.badgen.net/badge/PHP/8.3?color=4E5B93&icon=php&label)
![Release](https://flat.badgen.net/packagist/v/bnomei/kirby-mcp?color=ae81ff&icon=github&label)
![Downloads](https://flat.badgen.net/packagist/dt/bnomei/kirby-mcp?color=272822&icon=github&label)
![Unittests](https://github.com/bnomei/kirby-mcp/actions/workflows/pest-tests.yml/badge.svg)
![PHPStan](https://github.com/bnomei/kirby-mcp/actions/workflows/phpstan.yml/badge.svg)
[![Discord](https://flat.badgen.net/badge/discord/bnomei?color=7289da&icon=discord&label)](https://discordapp.com/users/bnomei)
[![Buymecoffee](https://flat.badgen.net/badge/icon/donate?icon=buymeacoffee&color=FF813F&label)](https://www.buymeacoffee.com/bnomei)

Give your coding agent access to your Kirby project and the knowledge to work with it. Kirby MCP connects your agent to project structure, live content, runtime debugging, and a bundled Kirby knowledge base.

For Composer-based Kirby projects. **Kirby 6 · PHP 8.3+ · Composer**

[Capabilities](#capabilities) · [Knowledge base](#kirby-knowledge-built-in) · [Quickstart](#quickstart) · [Connect your agent](#connect-your-agent) · [Usage](#usage) · [Tools and resources](#tools-and-resources) · [Documentation](#documentation)

## Capabilities

| Your agent can…             | Use it to…                                                                                              |
| --------------------------- | ------------------------------------------------------------------------------------------------------- |
| **Understand your project** | Inspect blueprints, templates, snippets, collections, models, plugins, routes, and configuration.       |
| **Find Kirby answers**      | Search the bundled knowledge base, official documentation, and plugin directory.                        |
| **Work with content**       | Read page, file, site, and user content; update it with confirmation and field-specific guidance.       |
| **Debug real pages**        | Render a page, inspect errors, and collect values with [`mcp_dump()`](#debug-a-page).                   |
| **Support development**     | Follow [bundled skills](#use-bundled-skills) for common tasks and generate [IDE helpers](#ide-helpers). |

Your agent discovers the tools and resources through MCP. You can ask in plain language; you do not need to memorize tool names. Browse the [tool and resource lists](#tools-and-resources) below to see the full range.

Expose your own project or plugin commands as [custom MCP tools](docs/custom-tools.md), with descriptions, validated inputs and explicit CLI allowlisting. The same Kirby command can also serve Janitor; no separate job system is added.

### Kirby knowledge, built in

**Kirby MCP ships with a curated knowledge base, not just access to your site.** Your agent can search and read the bundled documents locally, without a network request:

- **Kirby glossary:** concise explanations of concepts such as collections, models, hooks, and representations.
- **Task recipes:** practical steps for building forms, scaffolding page types, routing, multilingual content, and other everyday work.
- **Content update guides:** how fields store values, what update payloads should look like, and what to check before and after a write.
- **Panel development guides:** blueprint patterns, custom fields, plugin recipes, and editor/runtime references.

The guides connect explanations to the relevant MCP tools and resources, so your agent can move from learning a Kirby concept to inspecting and working with your actual project. For example, the page-picker guide explains stored references, shows an update payload, and points to the tools for reading and verifying the content.

Use `kirby_search` to find a topic, or read documents through `kirby://kb` and `kirby://kb/{path}`. Official documentation and plugin-directory searches provide online lookups when you need to go further. You can also [browse the bundled Markdown](kb/) yourself.

## Quickstart

From your Kirby project root:

```bash
composer require --dev bnomei/kirby-mcp:"^6.0"
vendor/bin/kirby-mcp install
```

Then [connect your agent](#connect-your-agent). Your MCP client starts the server; no separate running terminal is needed.

> [!IMPORTANT]
> The verified Kirby CMS baseline is **6.0.0-beta.1**. Kirby MCP's stable version does not make the CMS beta production-ready. Install Kirby separately and explicitly opt into its beta, for example with `composer require getkirby/cms:"6.0.0-beta.1" --with-all-dependencies` on a test copy before installing MCP. For **Kirby 5**, use `bnomei/kirby-mcp:"^1.15"` instead.

The installer adds project-local runtime commands, a small Kirby plugin adapter, and `.kirby-mcp/mcp.json`. The MCP library stays in `vendor/`. [Maintenance](#maintenance) explains updates and removal.

**Connecting a hosted site?** Follow the [remote installation tutorial](docs/install/remote.md) for production installation, HTTPS, OAuth, and a restricted Kirby account. Advanced authentication options remain in the [HTTP reference](docs/remote.md). **Want reference access without a project?** See [global reference mode](docs/global-reference.md).

## Connect your agent

Step-by-step guides cover registration, authorization, a read-only connection check, and troubleshooting:

| Cloud assistants                                      | Coding clients                                                   |
| ----------------------------------------------------- | ---------------------------------------------------------------- |
| [Claude web/Desktop](docs/install/claude.md)          | [Claude Code](docs/install/claude-code.md)                       |
| [ChatGPT](docs/install/chatgpt.md)                    | [Codex CLI](docs/install/codex.md)                               |
| [Langdock](docs/install/langdock.md)                  | [Cursor](docs/install/cursor.md)                                 |
| [Choose local or remote setup](docs/install/index.md) | [Other clients, VS Code, and Amp](docs/install/other-clients.md) |

Client availability and authentication support vary; the tutorials link to official requirements and distinguish documented setup from live-tested compatibility. For a local project, the short configurations below are enough to get started.

Run these commands from the Kirby project root. They save an absolute executable path and project root so the client can find both when launched elsewhere.

### Claude Code

```bash
claude mcp add kirby -- "$PWD/vendor/bin/kirby-mcp" "--project=$PWD"
```

### Codex CLI

```bash
codex mcp add kirby -- "$PWD/vendor/bin/kirby-mcp" "--project=$PWD"
```

Codex saves MCP registrations at user scope. Use a distinct server name instead of `kirby` for each project.

### Cursor and other clients

For Cursor, add this to `.cursor/mcp.json`, replacing both paths:

```json
{
  "mcpServers": {
    "kirby": {
      "command": "/absolute/path/to/project/vendor/bin/kirby-mcp",
      "args": ["--project=/absolute/path/to/project"]
    }
  }
}
```

For other clients, configure the same command and arguments as a stdio MCP server. Reload your client if it does not discover the server immediately.

## Usage

### Start with a task

Try one of these prompts in your connected agent:

```text
Use Kirby MCP to give me a tour of this project's blueprints, templates, and plugins.
```

```text
Use Kirby MCP to plan a contact form using this project's conventions.
```

```text
Read the about page with Kirby MCP and help me update its title.
```

```text
Search the Kirby knowledge base for collection filtering. Check the official docs online if needed.
```

Asking for a plan is a useful way to inspect the project before making changes. Your agent can also read resources such as `kirby://glossary/collection` directly.

### Use bundled skills

Skills give your agent task-specific guidance: project tours, page scaffolding, Panel blueprints, content migrations, routing, plugins, forms, translations, security, and more. See the [skills catalog](docs/reference.md#skills-catalog).

Copy them into your agent's skills directory. For Claude Code:

```bash
mkdir -p ~/.claude/skills
rsync -a vendor/bnomei/kirby-mcp/skills/ ~/.claude/skills/
```

For Codex, use `~/.codex/skills/` instead. Restart the agent after copying; repeat the copy after package updates to refresh the skills.

### Debug a page

Add a dump where you need to inspect a value:

```php
mcp_dump($page->title()->value())->label('Page title');
```

Ask your agent to render the page with `kirby_render_page`, then read the captured values with `kirby_dump_log_tail` using the returned `traceId`. Dumps are written to `.kirby-mcp/dumps.jsonl`; secret masking is on by default, but inspect output before sharing it.

For deeper inspection, query execution is enabled with confirmation. PHP eval is disabled until you [enable it](docs/configuration.md#project-options).

### IDE helpers

Ask your agent to check or generate IDE helpers, or run:

```bash
vendor/bin/kirby-mcp ide:status
vendor/bin/kirby-mcp ide:generate --write
```

Generated helpers live in `.kirby-mcp/`. Without `--write`, generation is a dry run.

### Panel activity indicator

See recent MCP activity at the top center of the Kirby Panel:

![Kirby Panel header with the orange robot indicator at the top center and “MCP activity just now” beneath it](docs/images/panel-activity.png)

The indicator is **off by default**. To turn it on, set this in `.kirby-mcp/mcp.json` alongside your other settings:

```json
{
  "activity": { "enabled": true }
}
```

Run `vendor/bin/kirby-mcp update` to refresh the Panel assets, then reload the Panel. Kirby admins see the robot after a successful tool call or resource read. It fades from orange to grey and disappears after five minutes; hover, focus, or tap to see the elapsed time.

It shows **recent activity**, not whether an agent is connected or currently working. [Activity details](docs/configuration.md#panel-activity) cover timing and shared-filesystem requirements.

## Tools and resources

Tools let your agent inspect or act; resources bring reference material and project data into its context. These are the project server's capabilities; [global reference mode](docs/global-reference.md) exposes only the research subset, and remote permissions can narrow access.

### Tools

- **Project orientation:** `kirby_init`, `kirby_info`, `kirby_roots`, `kirby_composer_audit`, `kirby_cli_version`.
- **Blueprints:** `kirby_blueprints_index`, `kirby_blueprints_loaded`, `kirby_blueprint_read`.
- **Code and extensions:** `kirby_templates_index`, `kirby_snippets_index`, `kirby_collections_index`, `kirby_controllers_index`, `kirby_models_index`, `kirby_plugins_index`, `kirby_routes_index`.
- **Read content:** `kirby_read_page_content`, `kirby_read_file_content`, `kirby_read_site_content`, `kirby_read_user_content`.
- **Browse and read attachments:** `kirby_list_children`, `kirby_list_files`, `kirby_read_file`, `kirby_view_image`.
- **Upload and move:** `kirby_upload_file`, `kirby_move_page` (preview first; pass `confirm: true` to execute).
- **Update content:** `kirby_update_page_content`, `kirby_update_file_content`, `kirby_update_site_content`, `kirby_update_user_content`.
- **Research and discovery:** `kirby_search`, `kirby_online`, `kirby_online_plugins`, `kirby_tool_suggest`.
- **Render and debug:** `kirby_render_page`, `kirby_dump_log_tail`, `kirby_query_dot`, `kirby_eval`, `kirby_run_cli_command`.
- **Runtime and IDE support:** `kirby_runtime_status`, `kirby_runtime_install`, `kirby_cache_clear`, `kirby_ide_helpers_status`, `kirby_generate_ide_helpers`.

Writes and execution follow the [defaults and safety controls](#defaults-and-safety). See the [tool reference](docs/reference.md#tools-by-task) for individual descriptions.

CLI tools and help resources accept canonical command names such as `make:template`, not filesystem paths or encoded aliases. OAuth upgrades require reauthorization for refresh tokens issued before grant-family tracking; see [remote access](docs/remote.md#built-in-oauth-for-claude-connectors).

### Resources

Resources are read-only. Replace `{…}` with the item you want; the [resource reference](docs/reference.md#resources-and-uri-templates) explains IDs and URI encoding.

- **Bundled knowledge:** `kirby://kb`, `kirby://kb/{path}`, `kirby://glossary`, `kirby://glossary/{term}`.
- **Field and blueprint update guides:** `kirby://fields/update-schema`, `kirby://field/{type}/update-schema`, `kirby://blueprints/update-schema`, `kirby://blueprint/{type}/update-schema`.
- **Panel field and section references:** `kirby://fields`, `kirby://field/{type}`, `kirby://sections`, `kirby://section/{type}`.
- **Hooks and extensions:** `kirby://hooks`, `kirby://hook/{name}`, `kirby://extensions`, `kirby://extension/{name}`.
- **Project and configuration:** `kirby://info`, `kirby://roots`, `kirby://composer`, `kirby://config/{option}`, `kirby://blueprint/{encodedId}`.
- **Content:** `kirby://page/content/{encodedIdOrUuid}`, `kirby://file/content/{encodedIdOrUuid}`, `kirby://site/content`, `kirby://user/content/{encodedIdOrEmail}`.
- **Original files:** `kirby://file/read/{encodedIdOrUuid}` returns text or a binary blob, not metadata.
- **Browsing:** `kirby://site/children`, `kirby://site/files`, `kirby://page/children/{encodedIdOrUuid}`, `kirby://page/files/{encodedIdOrUuid}`. Follow each listing's `nextUri` for more results.
- **CLI reference:** `kirby://commands`, `kirby://cli/command/{command}`.
- **Tool discovery and examples:** `kirby://tools`, `kirby://tool-examples`.
- **Utilities and extras:** `kirby://uuid/new`, `kirby://susie/{phase}/{step}`.

### Native browsing, uploads and moves

Browse pages and attachments, read original files, and view image previews using Kirby IDs or UUIDs. `content` remains metadata; `read` returns bytes. Uploads support local copies and single-use HTTP tickets; uploads and page moves require confirmation and native permissions.

See [Native browsing, uploads and moves](docs/native-content.md) for pagination, blueprint constraints, examples, limits, and remote permissions.

## Defaults and safety

You can start with the installed configuration. Change settings only for the capabilities you need.

| Capability                                                  | Default                                                 |
| ----------------------------------------------------------- | ------------------------------------------------------- |
| Project inspection, documentation search, and content reads | Available after setup                                   |
| Content updates                                             | Require confirmation                                    |
| Query execution                                             | Enabled; requires confirmation                          |
| PHP eval                                                    | Disabled; enabling it still requires confirmation       |
| Generic Kirby CLI commands                                  | Allowlisted; write commands require explicit permission |
| Debug dumps and secret masking                              | Enabled                                                 |
| Panel activity indicator                                    | Disabled                                                |
| Remote HTTP access                                          | Disabled                                                |

Local MCP access is trusted access to your project. Confirmation guards MCP operations, not everything your coding agent can do through its other tools. Enabled PHP eval and generic CLI execution are not sandboxed. Keep `.kirby-mcp/` outside your public document root or deny web access to it.

> [!WARNING]
> Retrieved documents can contain prompt injection. Review proposed changes and do not assume an agent's output or a confirmation prompt makes an action safe.

For custom settings, use the [configuration reference](docs/configuration.md). Before exposing the server remotely, read the [authentication and permission boundaries](docs/remote.md#security-boundaries).

## Maintenance

After upgrading the package, refresh the installed runtime files:

```bash
composer update bnomei/kirby-mcp
vendor/bin/kirby-mcp update
```

`update` overwrites the copied runtime commands and plugin adapter, but preserves your configuration. They normally live in `site/commands/mcp/` and `site/plugins/kirby-mcp/`; custom Kirby roots are respected.

To uninstall, remove those two directories and the Composer dependency. Delete `.kirby-mcp/` only if you also want to remove configuration, dumps, generated helpers, and remote authentication state. Remove the server entry from your MCP client and any HTTP routes you registered.

## Troubleshooting

- **Project not found:** run from the project root or pass `--project=/absolute/path/to/project`.
- **Runtime tools fail:** run `vendor/bin/kirby-mcp install` and ask the agent to check `kirby_runtime_status`.
- **CLI command blocked:** review `cli.allow`, `cli.allowWrite`, and `cli.deny` in the [configuration reference](docs/configuration.md#project-options).
- **Wrong host-specific config:** set `KIRBY_MCP_HOST` or `kirby.host`.
- **No dump output:** check `dumps.enabled`, the dump file's permissions, and the render's `traceId`.
- **Remote connection fails:** use the [HTTP troubleshooting checklist](docs/remote.md#troubleshoot-http).
- **Global server fails to start:** check the [absolute executable path](docs/global-reference.md#troubleshoot-startup-and-discovery-separately).

Server diagnostics go to stderr. Include the client version and relevant error when [reporting a problem](https://github.com/bnomei/kirby-mcp/issues/new); remove secrets first. See [compatibility notes](docs/reference.md#compatibility-and-reference-limits) for the current CMS baseline and known upstream limitations.

## Documentation

| Go deeper                                         | What you'll find                                        |
| ------------------------------------------------- | ------------------------------------------------------- |
| [Installation tutorials](docs/install/index.md)   | Local/remote setup and step-by-step client connections  |
| [Tools, resources, and skills](docs/reference.md) | Complete inventory, content schemas, and MCP behavior   |
| [Configuration](docs/configuration.md)            | Every setting and environment variable                  |
| [Custom tools](docs/custom-tools.md)              | Expose project/plugin CLI commands, including Janitor reuse |
| [Remote connections](docs/remote.md)              | HTTP setup, OAuth, bearer tokens, and Kirby permissions |
| [Global reference mode](docs/global-reference.md) | Projectless installation and client setup               |

For development and contributions, see [Contributing](CONTRIBUTING.md) and [Testing](TESTING.md). Release history lives in the [Changelog](CHANGELOG.md); report vulnerabilities using the [Security policy](SECURITY.md).

## Disclaimer

This MCP server is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it
in a production environment. If you find any issues,
please [create a new issue](https://github.com/bnomei/kirby-mcp/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this MCP server in any project that promotes racism, sexism, homophobia, animal abuse, violence or
any other form of hate speech.
