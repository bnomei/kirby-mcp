# Kirby MCP

[![Kirby 5](https://flat.badgen.net/badge/Kirby/5?color=ECC748)](https://getkirby.com)
![PHP 8.2](https://flat.badgen.net/badge/PHP/8.2?color=4E5B93&icon=php&label)
![Release](https://flat.badgen.net/packagist/v/bnomei/kirby-mcp?color=ae81ff&icon=github&label)
![Downloads](https://flat.badgen.net/packagist/dt/bnomei/kirby-mcp?color=272822&icon=github&label)
[![Coverage](https://flat.badgen.net/codeclimate/coverage/bnomei/kirby-mcp?icon=codeclimate&label)](https://codeclimate.com/github/bnomei/kirby-mcp)
[![Maintainability](https://flat.badgen.net/codeclimate/maintainability/bnomei/kirby-mcp?icon=codeclimate&label)](https://codeclimate.com/github/bnomei/kirby-mcp/issues)
[![Discord](https://flat.badgen.net/badge/discord/bnomei?color=7289da&icon=discord&label)](https://discordapp.com/users/bnomei)
[![Buymecoffee](https://flat.badgen.net/badge/icon/donate?icon=buymeacoffee&color=FF813F&label)](https://www.buymeacoffee.com/bnomei)

CLI-first MCP server for Kirby CMS (composer-based Kirby projects). It lets an IDE/agent inspect your Kirby project
(blueprints, templates, plugins, docs) and—when needed—interact with a real Kirby runtime via installed command wrappers.

## Quickstart

From your Kirby project root:

```bash
composer require bnomei/kirby-mcp --dev
vendor/bin/kirby-mcp install
vendor/bin/kirby-mcp
```

Then configure your MCP client (Cursor/Claude Code/Codex CLI) using the examples in **Client setup**.

## Copy-paste prompt examples

Use these once your MCP client is connected to the server.

```text
Use the Kirby MCP to make a plan to ...
```

```text
Append " with AI" to the title of the home page with Kirby MCP.
```

```text
Show me the fields available on the home page blueprint and what they do using the MCP.
```

```text
kirby search for collection filtering
```

```text
kirby search online for panel permissions
```

```text
kirby://config/debug
```

```text
kirby://glossary/collection
```

```text
My home page renders incorrectly. Help me debug it with mcp_dump() to return the current $page object.
```

```text
kirby MCP tinker $site->index()->count()
```

## IDE helpers (optional, for humans)

- Check baseline + freshness: `vendor/bin/kirby-mcp ide:status` (use `--details` and `--limit=N` for more output)
- Generate regeneratable helper files: `vendor/bin/kirby-mcp ide:generate` (default is `--dry-run`; add `--write` to create files)
- JSON output: `--json` (MCP markers) or `--raw-json` (plain JSON)

## What it does (and doesn’t)

- Provides MCP tools/resources for project inspection (blueprints, templates/snippets/collections, controllers/models, plugins, routes, roots).
- Fetches official Kirby reference docs and ships a local Markdown knowledge base (`kb/`) for fast lookups.
- Doesn’t modify your content by default; write-capable actions are guarded and require explicit opt-in/confirmation.
- Only supports composer-based Kirby projects (Kirby CLI is used for many capabilities).

## What `install` / `update` change in your project

`vendor/bin/kirby-mcp install`:

- Creates `.kirby-mcp/mcp.json` if neither `.kirby-mcp/mcp.json` nor `.kirby-mcp/config.json` exist.
- Copies runtime command wrappers into the project’s Kirby commands root (usually `site/commands/mcp/`).
- Use `--force` to overwrite existing wrapper files.

`vendor/bin/kirby-mcp update`:

- Overwrites the runtime wrappers (use after upgrading this package).
- Creates `.kirby-mcp/mcp.json` only if missing; it won’t overwrite an existing config.

To remove everything:

- Delete the runtime wrappers folder (`site/commands/mcp/` in most projects).
- Optionally delete `.kirby-mcp/` (config + caches + optional helper files).

## Security model

- `kirby_run_cli_command` is guarded by an allowlist; extend it via `.kirby-mcp/mcp.json` (`cli.allow`, `cli.allowWrite`) and block via `cli.deny`.
- Write-capable actions require explicit opt-in (e.g. `allowWrite=true` or `confirm=true`, depending on the tool).
- `kirby_eval` is disabled by default; enable via `KIRBY_MCP_ENABLE_EVAL=1` or `.kirby-mcp/mcp.json` (`{"eval":{"enabled":true}}`) and still confirm per call.

## Capabilities

Some capabilities require the runtime wrappers (installed via `vendor/bin/kirby-mcp install`) because they query Kirby at runtime.

> [!IMPORTANT]
> `kirby_init` is required once per session before calling any other tool but the agent should figure this out automatically.

<details>
<summary>Tools</summary>

- `kirby_blueprint_read` — read a single blueprint by id
- `kirby_blueprints_index` — index blueprints, includes plugin-registered ones when runtime is installed
- `kirby_blueprints_loaded` — list blueprint ids loaded at runtime
- `kirby_cache_clear` — clear in-memory caches for this MCP session (StaticCache, config, composer, roots, tool index)
- `kirby_cli_version` — run `kirby version` and return stdout, stderr and exit code
- `kirby_composer_audit` — parse composer.json and composer.lock for scripts and quality tools
- `kirby_collections_index` — index named collections, includes plugin-registered ones when runtime is installed
- `kirby_controllers_index` — index controllers, includes plugin-registered ones when runtime is installed
- `kirby_online` — search official Kirby docs (online fallback) and optionally fetch markdown pages
- `kirby_dump_log_tail` — tail `.kirby-mcp/dumps.jsonl` written by `mcp_dump()`
- `kirby_eval` — execute PHP in Kirby runtime for quick inspection, requires enable plus confirm
- `kirby_generate_ide_helpers` — generate regeneratable IDE helper files into `.kirby-mcp/`
- `kirby_ide_helpers_status` — report missing template/snippet PHPDoc `@var` hints + helper file freshness (mtime-based)
- `kirby_info` — project runtime info, composer audit and local environment detection
- `kirby_init` — session guidance plus project-specific audit, call once per session
- `kirby_search` — search the bundled local Kirby knowledge base markdown files (preferred)
- `kirby_models_index` — index registered page models with class and file path info
- `kirby_plugins_index` — index loaded plugins, prefers runtime truth when installed
- `kirby_read_page_content` — read page content by id or uuid
- `kirby_render_page` — render a page by id or uuid and return HTML plus errors
- `kirby_roots` — resolved Kirby roots via `kirby roots`
- `kirby_routes_index` — list registered routes with best-effort source location (config/plugin)
- `kirby_run_cli_command` — run a Kirby CLI command, guarded by an allowlist
- `kirby_runtime_install` — install project-local Kirby MCP runtime CLI commands into the project
- `kirby_runtime_status` — check whether runtime command wrappers are installed
- `kirby_snippets_index` — index snippets, includes plugin-registered ones when runtime is installed
- `kirby_templates_index` — index templates, includes plugin-registered ones when runtime is installed
- `kirby_tool_suggest` — suggest the best next Kirby MCP tool/resource for a task
- `kirby_update_page_content` — update page content, plus confirm

</details>

<details>

> [!TIP]
> Call a resource to bring condensed knowledge into the current context of your agent.

<summary>Resources</summary>

Resources (read-only):

- `kirby://commands` — Kirby CLI command list, parsed from `kirby help`
- `kirby://composer` — composer audit, scripts and quality tooling
- `kirby://extensions` — Kirby plugin extensions list (links to `kirby://extension/{name}`)
- `kirby://fields` — Kirby Panel field types list (links to `kirby://field/{type}`)
- `kirby://glossary` — Kirby glossary terms list (links to `kirby://glossary/{term}`)
- `kirby://hooks` — Kirby hook names list (links to `kirby://hook/{name}`)
- `kirby://info` — project runtime info, composer audit and local environment detection
- `kirby://prompts` — MCP prompts with args/meta (fallback for clients without prompt support)
- `kirby://roots` — Kirby roots discovered via CLI, respects configured host
- `kirby://sections` — Kirby Panel section types list (links to `kirby://section/{type}`)
- `kirby://tools` — weighted keyword index for Kirby MCP tools/resources/templates

Resource templates (dynamic):

- `kirby://blueprint/{encodedId}` — read a blueprint by URL-encoded id, e.g. `pages%2Fhome`
- `kirby://cli/command/{command}` — parsed `kirby <command> --help` output, e.g. `backup` or `uuid:generate`
- `kirby://config/{option}` — read a Kirby config option by dot path
- `kirby://extension/{name}` — Kirby extension reference markdown from getkirby.com, e.g. `commands` or `darkroom-drivers`
- `kirby://field/{type}` — Kirby Panel field reference markdown from getkirby.com, e.g. `blocks` or `email`
- `kirby://glossary/{term}` — read a bundled Kirby glossary entry by term, e.g. `api` or `kql`
- `kirby://hook/{name}` — Kirby hook reference markdown from getkirby.com, e.g. `file.changeName:after` or `file-changename-after`
- `kirby://page/content/{encodedIdOrUuid}` — read page content by URL-encoded id or uuid
- `kirby://prompt/{name}` — prompt details + rendered default messages (fallback for clients without prompt support)
- `kirby://section/{type}` — Kirby Panel section reference markdown from getkirby.com, e.g. `fields` or `files`
- `kirby://susie/{phase}/{step}` — easter egg resource template

</details>

<details>
<summary>Prompts & completions</summary>

Prompts:

- `kirby_project_tour` — map the project (roots + inventory) and suggest next steps
- `kirby_debug_render_trace` — debug via `kirby_render_page` + `mcp_dump` traces (`kirby_dump_log_tail`)
- `kirby_scaffold_page_type` — scaffold a new page type (blueprint + template + optional controller/model)
- `kirby_content_migration_assistant` — plan/apply safe content migrations (read/update page content)
- `kirby_ide_support_boost` — improve IDE support + optional helper generation (`.kirby-mcp/`)
- `kirby_upgrade_kirby` — upgrade Kirby safely (docs + composer + verification)
- `kirby_performance_audit` — guide an agent through a Kirby performance audit, cache and query pitfalls

Prompt fallback resources (for clients without MCP prompt support):

- `kirby://prompts` and `kirby://prompt/{name}`

Completions:

- Prompts and resource templates provide parameter completions (e.g. blueprint ids + config hosts).

</details>

## Client setup

> [!NOTE]
> The `--project` flag is optional when you run the server from the Kirby project root.
> Use it (or `KIRBY_MCP_PROJECT_ROOT`) when running from elsewhere or from a global MCP config.

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

### Manual

Start the server (point it at a composer-based Kirby project):

- From the Kirby project root: `vendor/bin/kirby-mcp`
- Or explicit: `vendor/bin/kirby-mcp --project=/absolute/path/to/kirby-project`

## Configuration

Project config lives in `.kirby-mcp/mcp.json` (or `.kirby-mcp/config.json`) in the Kirby project root.
It is created by `vendor/bin/kirby-mcp install` if missing.

Kirby host selection:

- By default, Kirby CLI runs with no `KIRBY_HOST` override.
- To use host-specific Kirby config, set `KIRBY_MCP_HOST` (or `KIRBY_HOST`) when starting the MCP server, or set `kirby.host` in `.kirby-mcp/mcp.json`:
  - `{"kirby":{"host":"localhost"}}`

| Option                  | Type       | Default   | Description                                                                                                                                                                                                  |
| ----------------------- | ---------- | --------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `cache.ttlSeconds`      | `int`      | `60`      | In-memory cache TTL (seconds) for read-only resources like `kirby://commands` and `kirby://cli/command/{command}` plus a few internal caches (roots inspection, completions); set to `0` to disable caching. |
| `docs.ttlSeconds`       | `int`      | `86400`   | In-memory cache TTL (seconds) for fetched getkirby.com markdown docs (e.g. `kirby://field/{type}` and `kirby://section/{type}`); set to `0` to disable caching.                                              |
| `cli.allow`             | `string[]` | `[]`      | Additional allowlist patterns for `kirby_run_cli_command` (supports `*` wildcard, e.g. `plugin:*`).                                                                                                          |
| `cli.allowWrite`        | `string[]` | `[]`      | Additional allowlist patterns for write-capable commands; requires `allowWrite=true` when calling `kirby_run_cli_command` (supports `*`).                                                                    |
| `cli.deny`              | `string[]` | `[]`      | Deny patterns that always block commands, even if allowlisted (supports `*`).                                                                                                                                |
| `dumps.enabled`         | `bool`     | `true`    | Enable/disable `mcp_dump()` writes to `.kirby-mcp/dumps.jsonl`.                                                                                                                                              |
| `dumps.maxBytes`        | `int`      | `2097152` | Max size for `.kirby-mcp/dumps.jsonl` written by `mcp_dump()`. When the next write would exceed it, the log is compacted by keeping the newest half of lines, then the new entry is appended.                |
| `ide.typeHintScanBytes` | `int`      | `16384`   | Max bytes to read from controller/model files when detecting Kirby IDE baseline type hints (see `kirby_ide_helpers_status`).                                                                                 |
| `kirby.host`            | `string`   | `null`    | Default Kirby host to pass as `KIRBY_HOST` to the Kirby CLI (affects host-specific config like `config.{host}.php`).                                                                                         |
| `eval.enabled`          | `bool`     | `false`   | Enable `kirby_eval` / `kirby mcp:eval` (still requires explicit confirmation per call).                                                                                                                      |

Environment variables:

| Env var                         | Description                                                                    |
| ------------------------------- | ------------------------------------------------------------------------------ |
| `KIRBY_MCP_PROJECT_ROOT`        | Project root (overrides auto-detection).                                       |
| `KIRBY_MCP_KIRBY_BIN`           | Path to `vendor/bin/kirby` (overrides binary resolution).                      |
| `KIRBY_MCP_HOST` / `KIRBY_HOST` | Kirby host override (takes precedence over config).                            |
| `KIRBY_MCP_DUMPS_ENABLED`       | Override `dumps.enabled` (`1/0`, `true/false`, `on/off`).                      |
| `KIRBY_MCP_ENABLE_EVAL`         | Enable eval override (takes precedence over config; still needs confirmation). |

## Debug dumps (`mcp_dump`)

This package provides a lightweight `mcp_dump()` helper that appends JSONL to `.kirby-mcp/dumps.jsonl` in the project root.

Typical workflow for your coding agent:

- Add `mcp_dump($anything)` (optionally chain `->green()`, `->label('...')`, `->caller()`, `->trace()`, `->pass($value)`) anywhere in templates/snippets/controllers.
- Call `kirby_render_page` (it returns a `traceId`).
- Call `kirby_dump_log_tail(traceId=...)` to retrieve the captured dump events for that render.

## Troubleshooting

- “Unable to determine Kirby project root”: run from the Kirby project root or pass `--project=/absolute/path` (or set `KIRBY_MCP_PROJECT_ROOT`).
- Runtime-only tools fail: run `vendor/bin/kirby-mcp install` and check `kirby_runtime_status`.
- CLI command blocked: add patterns to `.kirby-mcp/mcp.json` (`cli.allow` / `cli.allowWrite`) or block with `cli.deny`.
- Host-specific config not applied: set `KIRBY_MCP_HOST`/`KIRBY_HOST` or configure `{"kirby":{"host":"..."}}`.
- Docs resources are slow/failing: confirm network access or adjust `docs.ttlSeconds` (set to `0` to disable caching).
- No dump output: ensure `dumps.enabled=true`, a `.kirby-mcp/dumps.jsonl` exists, and use the correct `traceId` with `kirby_dump_log_tail`.

## Development

- Install deps: `composer install`
- Run tests: `composer test`
- Run static analysis: `composer analyse`

## Disclaimer

This MCP server is provided "as is" with no guarantee. Use it at your own risk and always test it yourself before using it
in a production environment. If you find any issues,
please [create a new issue](https://github.com/bnomei/kirby-mcp/issues/new).

## License

[MIT](https://opensource.org/licenses/MIT)

It is discouraged to use this MCP server in any project that promotes racism, sexism, homophobia, animal abuse, violence or
any other form of hate speech.
