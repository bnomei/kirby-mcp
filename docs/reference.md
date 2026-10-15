# Tools, resources and skills

[README](../README.md) · [Configuration](configuration.md) · [Remote HTTP and permissions](remote.md) · [Global reference mode](global-reference.md)

Use `kirby_tool_suggest` or `kirby://tools` to find the next operation. Run `kirby_init` once per stdio handshake session before other tools. HTTP does not require this tool, but it remains useful for project audit and guidance; it is optional for stateless `2026-07-28` calls. Runtime-backed operations need installed wrappers and a supported Kirby CMS.

## Tools by task

### Orient and inspect a project

| Tool                      | Purpose                                                                             |
| ------------------------- | ----------------------------------------------------------------------------------- |
| `kirby_init`              | Session guidance and project audit; global mode returns reference guidance instead. |
| `kirby_info`              | Runtime info, Composer audit and local environment detection.                       |
| `kirby_roots`             | Resolve Kirby roots through CLI, respecting the configured host.                    |
| `kirby_composer_audit`    | Inspect Composer scripts and quality tooling.                                       |
| `kirby_cli_version`       | Run `kirby version`, returning stdout, stderr and exit code.                        |
| `kirby_blueprints_index`  | Index filesystem and, when runtime is installed, plugin-registered blueprints.      |
| `kirby_blueprints_loaded` | List blueprint IDs loaded at runtime.                                               |
| `kirby_blueprint_read`    | Read one raw blueprint by ID.                                                       |
| `kirby_templates_index`   | Index templates, including runtime plugin registrations when available.             |
| `kirby_snippets_index`    | Index snippets, including runtime plugin registrations when available.              |
| `kirby_collections_index` | Index named collections, including runtime registrations when available.            |
| `kirby_controllers_index` | Index controllers, including runtime registrations when available.                  |
| `kirby_models_index`      | List registered page models with class and file paths.                              |
| `kirby_plugins_index`     | List plugins, preferring runtime truth when installed.                              |
| `kirby_routes_index`      | List routes with best-effort config/plugin source locations.                        |

### Read and update content

| Tool                        | Purpose                                   |
| --------------------------- | ----------------------------------------- |
| `kirby_read_page_content`   | Read page content by ID or UUID.          |
| `kirby_read_file_content`   | Read file content/metadata by ID or UUID. |
| `kirby_read_site_content`   | Read site content.                        |
| `kirby_read_user_content`   | Read user content by ID or email.         |
| `kirby_update_page_content` | Confirmed page-content update.            |
| `kirby_update_file_content` | Confirmed file metadata/content update.   |
| `kirby_update_site_content` | Confirmed site-content update.            |
| `kirby_update_user_content` | Confirmed user-content update.            |

Read first, inspect `fieldSchemas`, then use the matching blueprint and field update-schema resources for payload shapes. Update `data` accepts a JSON object or a JSON-encoded object string. Remote updates run as the authenticated Kirby user; [capabilities and native mutation checks](remote.md#security-boundaries) both apply.

### Research Kirby

| Tool                   | Purpose                                                            |
| ---------------------- | ------------------------------------------------------------------ |
| `kirby_search`         | Search bundled local KB Markdown; preferred first lookup.          |
| `kirby_online`         | Search official Kirby docs and optionally fetch Markdown.          |
| `kirby_online_plugins` | Search the official plugin directory and optionally fetch details. |
| `kirby_tool_suggest`   | Suggest tools/resources for a task.                                |

These tools plus `kirby_init` form the smaller [global profile](global-reference.md).

### Render, debug and execute

| Tool                    | Purpose                                                                           |
| ----------------------- | --------------------------------------------------------------------------------- |
| `kirby_render_page`     | Render a page by ID/UUID; return HTML, errors and a trace ID.                     |
| `kirby_dump_log_tail`   | Tail events from `.kirby-mcp/dumps.jsonl`, optionally filtered by trace/path.     |
| `kirby_query_dot`       | Evaluate Kirby query-language strings; enabled by default but confirmation-gated. |
| `kirby_eval`            | Execute PHP in Kirby runtime; disabled by default and confirmation-gated.         |
| `kirby_run_cli_command` | Execute an allowlisted Kirby CLI command; writes require `allowWrite=true`.       |

For dot queries, UUID contexts resolve through Kirby's UUID API. Unknown field methods error; use bracket/member access for unusual names. Kirby TXT decoding normalizes dashes to underscores. Query/eval and generic CLI execution are trusted execution, not a restricted content API. See [configuration](configuration.md#project-options) and [dump storage](configuration.md#dump-storage-and-redaction).

### Maintain runtime and IDE support

| Tool                         | Purpose                                                                                      |
| ---------------------------- | -------------------------------------------------------------------------------------------- |
| `kirby_runtime_status`       | Check installed runtime wrappers.                                                            |
| `kirby_runtime_install`      | Install project-local runtime CLI commands.                                                  |
| `kirby_cache_clear`          | Clear current-process in-memory caches: static data, config, Composer, roots and tool index. |
| `kirby_ide_helpers_status`   | Check baseline PHPDoc hints and mtime-based generated-helper freshness.                      |
| `kirby_generate_ide_helpers` | Generate regeneratable IDE helper files under `.kirby-mcp/`.                                 |

## Resources and URI templates

Resources are read-only. URL-encode IDs inside URI path parameters (`pages/home` becomes `pages%2Fhome`). Concrete URIs can be read directly; placeholders must be filled first.

### Bundled and online reference

| Resource                                 | Purpose                                                                                            |
| ---------------------------------------- | -------------------------------------------------------------------------------------------------- |
| `kirby://kb`                             | Bundled KB index.                                                                                  |
| `kirby://kb/{path}`                      | KB document relative to `kb/`, without `.md`.                                                      |
| `kirby://glossary`                       | Glossary index.                                                                                    |
| `kirby://glossary/{term}`                | Bundled glossary entry, e.g. `collection`.                                                         |
| `kirby://fields`                         | Panel field-type index.                                                                            |
| `kirby://field/{type}`                   | Official Panel field reference Markdown.                                                           |
| `kirby://sections`                       | Panel section-type index.                                                                          |
| `kirby://section/{type}`                 | Official section reference Markdown.                                                               |
| `kirby://extensions`                     | Plugin-extension index.                                                                            |
| `kirby://extension/{name}`               | Official extension reference, e.g. `commands`.                                                     |
| `kirby://hooks`                          | Hook-name index.                                                                                   |
| `kirby://hook/{name}`                    | Official hook reference; accepts names such as `file.changeName:after` or `file-changename-after`. |
| `kirby://fields/update-schema`           | Bundled field-content guide index.                                                                 |
| `kirby://field/{type}/update-schema`     | Bundled field storage/update guide.                                                                |
| `kirby://blueprints/update-schema`       | Blueprint update-guide index.                                                                      |
| `kirby://blueprint/{type}/update-schema` | Bundled `page`, `file`, `site` or `user` update guide.                                             |
| `kirby://tool-examples`                  | Tool request examples.                                                                             |
| `kirby://susie/{phase}/{step}`           | Bundled easter-egg template.                                                                       |

### Project inspection and content

| Resource                                  | Purpose                                                                              |
| ----------------------------------------- | ------------------------------------------------------------------------------------ |
| `kirby://info`                            | Runtime info, Composer audit and environment detection.                              |
| `kirby://roots`                           | Resolved roots, respecting the configured host.                                      |
| `kirby://composer`                        | Composer scripts and quality tooling.                                                |
| `kirby://commands`                        | CLI command list parsed from `kirby help`.                                           |
| `kirby://cli/command/{command}`           | Parsed `kirby <command> --help`.                                                     |
| `kirby://config/{option}`                 | Kirby config value by dot path.                                                      |
| `kirby://blueprint/{encodedId}`           | Raw blueprint, e.g. `pages%2Fhome`.                                                  |
| `kirby://page/content/{encodedIdOrUuid}`  | Page content.                                                                        |
| `kirby://file/content/{encodedIdOrUuid}`  | File content/metadata.                                                               |
| `kirby://site/content`                    | Site content (a concrete URI).                                                       |
| `kirby://user/content/{encodedIdOrEmail}` | User content.                                                                        |
| `kirby://uuid/new`                        | New UUID string, respecting the project's `content.uuid` format.                     |
| `kirby://tools`                           | Weighted keyword tool/resource/template index; remote access is permission-filtered. |

For the exact remote authorization mapping, use the [complete capability map](remote.md#complete-capability-map), not the grouping on this page.

## Understand `fieldSchemas`

Raw blueprint inspection (`Kirby\Blueprint\Blueprint::load()`) does not normalize layouts or validate a model-bound blueprint. Content reads use normalized `blueprint()->fields()` and include `fieldSchemas`, retaining display fields for inspection.

- `hasValue: true` identifies Kirby `ValueField` subclasses; `false` identifies display fields; `null` means type resolution was unavailable.
- It is **not** write permission or Kirby's language-dependent `isStorable(language)` decision.
- `pagepicker`, `filepicker` and `userpicker` hold selections; `pagelist` and `filelist` are display-only.
- Update-schema links are included only for value fields with an existing bundled guide. Display/unresolved types omit them even if a similarly named guide exists. Guides remain directly readable through their reference resources.
- Unknown custom fields do not receive invented Panel reference URIs.
- Root blueprint field references use list entries (`- related`), not `related: true`.

Existing built-in sections remain supported by Kirby's automatic conversion and need no immediate rewrite. Prefer canonical fields for new blueprints; custom sections require ports. Section resources remain available for inspection and migration guidance.

## Confirmations and resource links

`kirby_update_*_content`, `kirby_eval`, and `kirby_query_dot` accept explicit `confirm=true`. Clients with MCP elicitation may instead show an inline confirmation and continue on acceptance. Authorization, enablement and confirmation are separate controls.

Modern confirmations bind to the complete operation inputs. Changing inputs on a retry makes the old response stale: the tool returns a safe preview with `confirmationStatus: "stale_input_ignored"` and `retryWithoutInputResponses: true`. Start a fresh call to confirm the changed operation.

Modern tool results with concrete `kirby://` references also include navigable resource links. URI templates remain structured references rather than clickable concrete resources.

## Cache and subscription semantics

Protocol `2026-07-28` clients receive one-hour public cache hints for deployment-static global-profile discovery and bundled KB/reference index reads in either profile. Externally fetched docs and project-specific results remain private and immediately stale. The permission-filtered remote `kirby://tools` index has zero cache TTL. These client hints are separate from the server's configurable [internal TTLs](configuration.md#project-options).

Successful `kirby_update_*_content` writes emit `notifications/resources/updated` for subscribed content resources: `kirby://site/content`, page, file and user content URIs. This is not a general filesystem watcher. HTTP authentication, session and cross-worker delivery limits are covered in [remote streaming](remote.md#sessions-streaming-and-subscriptions).

## Skills catalog

Bundled skills are installed at `vendor/bnomei/kirby-mcp/skills`; copy them to your agent's local skills directory using the [README client instructions](../README.md). Skills guide work; they do not grant tool permissions.

| Skill                               | Task                                                                                  |
| ----------------------------------- | ------------------------------------------------------------------------------------- |
| `kirby-project-tour`                | Inventory roots, blueprints and plugins; recommend next steps.                        |
| `kirby-content-migration`           | Plan safe content migrations using reads, updates and schemas.                        |
| `kirby-scaffold-page-type`          | Create a blueprint, template and optional controller/model using project conventions. |
| `kirby-routing-and-representations` | Routes, redirects and JSON/XML/RSS representations.                                   |
| `kirby-collections-and-navigation`  | Listings, pagination, search, filtering, sorting, grouping and menus.                 |
| `kirby-panel-and-blueprints`        | Panel UX, blueprint design, `extends`, custom areas/fields/sections.                  |
| `kirby-plugin-development`          | Hooks/extensions, KirbyTags, blocks and reusable plugin code.                         |
| `kirby-headless-api`                | Kirby API, KQL and JSON representations.                                              |
| `kirby-i18n-workflows`              | Languages, translation keys, localized labels and import/export.                      |
| `kirby-security-and-auth`           | Login, roles, permissions, access restrictions and protected downloads.               |
| `kirby-performance-and-media`       | Caches, CDN/media routing, responsive images and lazy loading.                        |
| `kirby-debugging-and-tracing`       | Render reproduction, `mcp_dump()` tracing and code-path discovery.                    |
| `kirby-ide-support`                 | Helper freshness and focused PHPDoc/type-hint improvements.                           |
| `kirby-upgrade-and-maintenance`     | Composer audit, plugin checks and upgrade verification.                               |
| `kirby-forms-and-frontend-actions`  | Forms, uploads, email and validated frontend page creation.                           |

## Compatibility and reference limits

MCP 6.x requires PHP 8.3+ and Kirby 6 for runtime operations. The verified CMS baseline is **6.0.0-beta.1**; a stable MCP release does not certify the CMS beta for production. Install the CMS separately with explicit beta opt-in. Kirby 7+ is unsupported; use MCP 1.x for Kirby 5. Projectless reference access does not require CMS.

Upstream Starterkit beta.1 has a Linux/case-sensitive-filesystem issue: Site Panel requests `js/Sortable.min.js` but the kit ships `js/sortable.min.js`, causing a dynamic-import 404. The Composer CMS package has the correct uppercase filename. This is an upstream kit asset issue, not an MCP activity-indicator requirement.

Online docs and marketplace availability depend on upstream services; a catalog entry does not prove every upstream URL or Kirby 6 plugin listing exists. Bundled implementation recipes are not certified third-party builds. Review compatibility in your project, including external dependencies and authentication integrations; see [remote integration limits](remote.md#troubleshoot-http).
