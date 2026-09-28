# Remote MCP permissions

Kirby MCP remains a Composer library, installed in `vendor/`. Runtime installation also writes a small
adapter into Kirby's resolved plugins root (`site/plugins/kirby-mcp` by default). Its `index.php` calls
`Bnomei\KirbyMcp\Mcp\Plugin::register()`; its generated `composer.json` supplies plugin metadata.
Neither file is a symlink. No server implementation or dependencies are copied into the plugin directory.
Run the runtime update command after upgrading the Composer package to refresh the copied adapter and metadata.

The adapter registers `bnomei/kirby-mcp` with Kirby. It registers permissions, not HTTP routes or Panel UI.
HTTP remains explicitly enabled and routed through the host configuration. Permission enforcement cannot
be switched off for remote connections. Local stdio and loopback shared-token connections remain trusted
operator access and do not require MCP role permissions.

OAuth uses the token's validated `sub` as the Kirby user ID. Remote static tokens must configure `userId`
with an existing Kirby user ID; there is no remote superuser fallback. Both modes also retain bearer-scope
checks. User identity is never accepted from MCP arguments, request metadata, or session state.

## Role configuration

Permissions are registered under Kirby's `bnomei.kirby-mcp` category, with dotted action names. Custom
roles default to denied; Kirby administrators retain their native all-permissions behavior.

```yaml
title: Content Editor
permissions:
  pages:
    update: true
    delete: false
  bnomei.kirby-mcp:
    '*': false
    access: true
    discovery: true
    content: true
    content.pages: true
    content.pages.read: true
    content.pages.update: true
    reference: true
    reference.schemas: true
    reference.examples: true
    reference.uuid: true
```

Every ancestor and the leaf must allow an operation. `content: false` blocks the entire content branch;
`content.pages: true` does not implicitly grant reads, updates, or rendering. A child cannot override a
denied parent. `access` is required for the remote connection. Unknown/unmapped capabilities are denied.

## Complete capability map

Tool names below omit `kirby_`. Resource placeholders stand for the existing URI-template parameters.
Each intermediate dotted prefix is also a registered permission, defaulting to false.

| Permission                    | Tools                                                     | Resources                                                                                                                                          |
| ----------------------------- | --------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| `discovery`                   | `tool_suggest`                                            | `kirby://tools`                                                                                                                                    |
| `content.pages.read`          | `read_page_content`                                       | `kirby://page/content/{encodedIdOrUuid}`                                                                                                           |
| `content.pages.update`        | `update_page_content`                                     | —                                                                                                                                                  |
| `content.pages.render`        | `render_page`                                             | —                                                                                                                                                  |
| `content.files.read`          | `read_file_content`                                       | `kirby://file/content/{encodedIdOrUuid}`                                                                                                           |
| `content.files.update`        | `update_file_content`                                     | —                                                                                                                                                  |
| `content.site.read`           | `read_site_content`                                       | `kirby://site/content`                                                                                                                             |
| `content.site.update`         | `update_site_content`                                     | —                                                                                                                                                  |
| `content.users.read`          | `read_user_content`                                       | `kirby://user/content/{encodedIdOrEmail}`                                                                                                          |
| `content.users.update`        | `update_user_content`                                     | —                                                                                                                                                  |
| `project.info`                | `init`, `info`                                            | `kirby://info`                                                                                                                                     |
| `project.roots`               | `roots`                                                   | `kirby://roots`                                                                                                                                    |
| `project.config`              | —                                                         | `kirby://config/{option}`                                                                                                                          |
| `project.blueprints`          | `blueprints_index`, `blueprints_loaded`, `blueprint_read` | `kirby://blueprint/{encodedId}`                                                                                                                    |
| `project.templates`           | `templates_index`                                         | —                                                                                                                                                  |
| `project.snippets`            | `snippets_index`                                          | —                                                                                                                                                  |
| `project.collections`         | `collections_index`                                       | —                                                                                                                                                  |
| `project.controllers`         | `controllers_index`                                       | —                                                                                                                                                  |
| `project.models`              | `models_index`                                            | —                                                                                                                                                  |
| `project.plugins`             | `plugins_index`                                           | —                                                                                                                                                  |
| `project.routes`              | `routes_index`                                            | —                                                                                                                                                  |
| `project.composer`            | `composer_audit`                                          | `kirby://composer`                                                                                                                                 |
| `project.cli`                 | `cli_version`                                             | `kirby://commands`, `kirby://cli/command/{command}`                                                                                                |
| `reference.knowledge`         | `search`                                                  | `kirby://kb`, `kirby://kb/{path}`, `kirby://susie/{phase}/{step}`                                                                                  |
| `reference.glossary`          | —                                                         | `kirby://glossary`, `kirby://glossary/{term}`                                                                                                      |
| `reference.schemas`           | —                                                         | `kirby://fields/update-schema`, `kirby://field/{type}/update-schema`, `kirby://blueprints/update-schema`, `kirby://blueprint/{type}/update-schema` |
| `reference.panel`             | —                                                         | `kirby://fields`, `kirby://field/{type}`, `kirby://sections`, `kirby://section/{type}`                                                             |
| `reference.extensions`        | —                                                         | `kirby://extensions`, `kirby://extension/{name}`                                                                                                   |
| `reference.hooks`             | —                                                         | `kirby://hooks`, `kirby://hook/{name}`                                                                                                             |
| `reference.examples`          | —                                                         | `kirby://tool-examples`                                                                                                                            |
| `reference.uuid`              | —                                                         | `kirby://uuid/new`                                                                                                                                 |
| `reference.online`            | `online`, `online_plugins`                                | —                                                                                                                                                  |
| `diagnostics.dumps`           | `dump_log_tail`                                           | —                                                                                                                                                  |
| `execution.query`             | `query_dot`                                               | —                                                                                                                                                  |
| `execution.php`               | `eval`                                                    | —                                                                                                                                                  |
| `execution.cli`               | `run_cli_command`                                         | —                                                                                                                                                  |
| `maintenance.runtime.status`  | `runtime_status`                                          | —                                                                                                                                                  |
| `maintenance.runtime.install` | `runtime_install`                                         | —                                                                                                                                                  |
| `maintenance.cache.clear`     | `cache_clear`                                             | —                                                                                                                                                  |
| `maintenance.ide.status`      | `ide_helpers_status`                                      | —                                                                                                                                                  |
| `maintenance.ide.generate`    | `generate_ide_helpers`                                    | —                                                                                                                                                  |

## Enforcement and limits

Remote discovery, the tool index, and tool suggestions show only permitted capabilities. Calls, resource
reads, completions, and resource subscription requests are checked independently; knowing a hidden
name or URI is not sufficient. Identity, role, and permission decisions are recomputed for each HTTP
request, not saved in MCP sessions. Revocation therefore applies to the next request, but an already
open streaming response is not continuously re-authorized.

The permission-filtered `kirby://tools` index is private with zero cache TTL. Resource bearer scopes
apply to modern and legacy subscriptions; opening a legacy GET/SSE stream rechecks every stored
subscription against the current token's scopes, independently of Kirby role permissions.

These are capability permissions, not per-page or per-field isolation. A permitted read capability may
read any target supported by that tool/resource. The four dedicated content-update tools additionally
execute as the Kirby user and obey native mutation checks and hooks. Confirmation remains required.
Enabled and permitted eval or generic CLI execution is trusted execution, not a sandbox: code can bypass
other permissions. This server must not be used to isolate untrusted users to a private subset of a site.

Kirby's custom permission extension is documented at
<https://getkirby.com/docs/reference/plugins/extensions/permissions>.
