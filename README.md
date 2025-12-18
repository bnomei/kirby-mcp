# kirby-mcp

CLI-first MCP server for Kirby CMS (composer projects only).

- Architecture + roadmap: `PLAN.md`
- Project-local Kirby knowledge base: `knowledge/`

## Install (in a Kirby project)
- `composer require bnomei/kirby-mcp --dev`

## Development
- Install deps: `composer install`
- Run tests: `composer test`
- Run static analysis: `composer analyse`

## MCP server (STDIO)
Start the server (point it at a composer-based Kirby project):
- From the Kirby project root: `vendor/bin/kirby-mcp`
- Or explicit: `vendor/bin/kirby-mcp --project=/absolute/path/to/kirby-project`

Kirby host selection:
- By default, Kirby CLI runs with no `KIRBY_HOST` override.
- To use host-specific Kirby config, set `KIRBY_MCP_HOST` (or `KIRBY_HOST`) when starting the MCP server, or set `.kirby-mcp/mcp.json`:
  - `{"kirby":{"host":"localhost"}}`

Current tools (very early):
- `kirby_init` (call first each session)
- `kirby_tool_suggest` (if unsure which tool to call)
- `kirby_docs_search`
- `kirby_project_info`
- `kirby_composer_audit`
- `kirby_cli_version`
- `kirby_list_cli_commands`
- `kirby_run_cli`
- `kirby_roots`
- `kirby_blueprints_index`
- `kirby_templates_index`
- `kirby_snippets_index`
- `kirby_controllers_index`
- `kirby_models_index`
- `kirby_plugins_index`
- `kirby_runtime_install`
- `kirby_runtime_status`
- `kirby_render_page`
- `kirby_read_page_content`
- `kirby_update_page_content`
- `kirby_blueprints_loaded`

Resources (read-only, early):
- `kirby://meta/tool-index`
- `kirby://project/info`
- `kirby://project/composer`
- `kirby://project/roots`
- `kirby://project/cli/commands`

Resource templates (dynamic):
- `kirby://blueprint/{encodedId}` (URL-encoded blueprint id, e.g. `pages%2Fhome`)
- `kirby://page/content/{encodedIdOrUuid}` (URL-encoded page id/uuid; requires runtime install)
- `kirby://susie/{phase}/{step}` (easter egg)

Prompts:
- `kirby_performance_audit`

Completions:
- Prompts and resource templates provide parameter completions (e.g. blueprint ids + config hosts).

## Client setup
### Cursor
Add to `.cursor/mcp.json` (project) or `~/.cursor/mcp.json` (global):

```json
{
  "mcpServers": {
    "kirby": {
      "command": "vendor/bin/kirby-mcp",
      "args": ["--project=/absolute/path/to/kirby-project"]
    }
  }
}
```

If you use the global config, set `"command"` to an absolute path to the project’s `vendor/bin/kirby-mcp` (or create a wrapper script).

### Claude Code
From the Kirby project directory:

```bash
claude mcp add kirby -- vendor/bin/kirby-mcp
```

Or explicit:

```bash
claude mcp add kirby -- vendor/bin/kirby-mcp --project=/absolute/path/to/kirby-project
```

### Codex CLI
From the Kirby project directory:

```bash
codex mcp add kirby -- vendor/bin/kirby-mcp
```

Or configure in `config.toml` under `[mcp_servers.kirby]` (see Codex CLI docs).
