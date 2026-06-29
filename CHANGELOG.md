# Changelog

All notable changes to this project will be documented in this file.
The format is based on Keep a Changelog and this project adheres to Semantic Versioning.

## [Unreleased]

## [1.10.0] - 2026-06-29

- Hardened HTTP MCP scope enforcement for runtime resources, blueprint reads, `mcp:*` CLI wrapper calls, write-capable CLI calls, and read-only remote-token defaults.
- Hardened shared-token and OAuth route handling with stricter loopback host validation, role-gated OAuth authorization, forced consent for resumed login sessions, and single-use authorization/refresh tokens.
- Redacted sensitive config and dump values more consistently, and required `kirby_dump_log_tail` calls to include a trace or path filter before reading shared dump logs.
- Fixed runtime command and CLI wrapper status reporting, including `kirby_run_cli_command` failure `ok` values, `kirby_runtime_install` success flags, and commands-root resolution for runtime install/update.
- Fixed runtime parsing and output edge cases for marked JSON framing, dotted page slugs in `kirby_query_dot`, malformed pagination limits, eval truncation, empty content payload schemas, failed roots inspection caching, and failed `--project` detection.

## [1.9.0] - 2026-06-16

- Added projectless global reference mode via `kirby-mcp --global` for always-available Kirby KB, glossary, reference docs, update schemas, official docs search, and plugin directory research.
- Added profile-gated MCP discovery so global reference mode exposes only docs/research/static reference tools and resources while project-local MCP servers keep the full project/runtime surface.
- Updated client setup docs with separate global reference and project-local MCP examples.
- Tightened global reference isolation so it rejects `--project` and project subcommands, skips project auto-detection, excludes cache/project administration tools, and avoids reading project config from global docs resources.

## [1.8.0] - 2026-06-02

- Updated MCP PHP SDK dependency to `mcp/sdk` v0.6.0.
- Added MCP spec-level titles to resources and resource templates.
- Added explicit HTTP MCP session TTL and SDK-backed session garbage-collection settings.
- Added SDK protocol-version validation to Streamable HTTP POST and GET/SSE requests while keeping the existing auth, origin, and CORS handling authoritative.

## [1.7.0] - 2026-05-22

- Added explicit `remote-token` HTTP auth for public Kirby `/mcp` routes used by header-capable clients, with hashed token records, per-token scopes, HTTPS enforcement for non-loopback requests, and unchanged query-string credential rejection.
- Added built-in, disabled-by-default OAuth provider routes for Claude Desktop/Claude.ai custom connectors, including metadata discovery, dynamic client registration, auth code + PKCE, refresh tokens, JWKS, and Kirby-user-backed login/consent storage under `.kirby-mcp/oauth`.
- Changed the built-in OAuth provider consent default to `snippet`, giving public Claude connector setups an explicit approve/deny step by default with a built-in form fallback.
- Expanded `KirbyMcpRoutes::routes()` to include the complete optional OAuth route set alongside `/mcp`.

## [1.6.1] - 2026-05-18

- Fixed MCP tool input schemas for array parameters so strict OpenAI-compatible function-calling clients no longer reject `tools/list` results. thanks @ralphsun73221
- Added regression coverage for missing `items` on array-typed tool input schemas and kept content update `data` schemas modeled as object-or-JSON-string inputs.

## [1.6.0] - 2026-05-18

- Added an explicit `KirbyMcpRoute::handle()` adapter for copy-paste Kirby `/mcp` route integration, including PSR-7-to-Kirby response bridging for Streamable HTTP responses.
- Updated HTTP docs to recommend the Kirby route integration only, with a production dependency note and no web-server proxy route.
- Hardened Kirby route shared-token mode so it remains loopback-only even when the route is installed on a public Kirby site.
- Fixed Kirby route project-root propagation and added CORS headers to Streamable HTTP GET/SSE responses.
- Added an opt-in `kirby-mcp http` listener for a Streamable HTTP MCP endpoint at `/mcp`; stdio remains the default transport.
- Added file-backed HTTP MCP sessions, GET SSE delivery, POST JSON-RPC handling, DELETE session cleanup, and authenticated CORS preflight support.
- Added mandatory Bearer auth for HTTP, shared-token loopback mode, query-string credential rejection, Origin validation, OAuth protected-resource metadata wiring, and per-operation scope enforcement.
- Documented HTTP configuration, security defaults, validation commands, and the current fail-closed OAuth listener limitation.
- Renamed the internal HTTP request handler from `HttpMcpTracer` to `HttpMcpHandler`.

## [1.5.0] - 2026-04-26

- Updated MCP PHP SDK dependency to `mcp/sdk` v0.5.0.
- Added MCP spec-level titles to tools and prompts, and exposed prompt titles through prompt resources.
- Reworked confirmation elicitation to use titled enum choices (`execute` / `preview`) while keeping legacy boolean confirmations working.
- Refreshed project dependencies, including Kirby CMS 5.4.0, Symfony 7.4 components, `symfony/finder`, and Prettier 3.8.3.

## [1.4.0] - 2026-02-24

- Updated MCP PHP SDK dependency to `mcp/sdk` v0.4.0.
- Added MCP resource update notifications for subscribed resources after successful content writes (`kirby_update_page_content`, `kirby_update_site_content`, `kirby_update_file_content`, `kirby_update_user_content`).
- Notifications use `notifications/resources/updated` and currently include only the changed resource `uri` (clients should re-read the resource for fresh content).
- Subscription tracking is session-scoped and event-based (emitted after successful write tools; out-of-band file/panel edits are not detected).
- Added optional client-side MCP elicitation confirmation for confirm-gated runtime tools (`kirby_update_page_content`, `kirby_update_site_content`, `kirby_update_file_content`, `kirby_update_user_content`, `kirby_eval`, `kirby_query_dot`) while keeping explicit `confirm=true` behavior and dry-run fallback.
- Kept backward compatibility for update-tool `data` payloads by accepting both JSON objects and JSON-encoded object strings in tool schemas and runtime parsing.

## [1.3.1] - 2026-01-12

- Updated MCP PHP SDK dependency to `mcp/sdk` v0.3.0.

## [1.3.0] - 2026-01-10

- Added `kirby_query_dot` tool and `mcp:query:dot` runtime command to evaluate Kirby query language strings.
- Minor improvements to 107 KB documents.
- Aligned Skills to Claude agent skills best practices with major improvements: https://platform.claude.com/docs/en/agents-and-tools/agent-skills/best-practices

## [1.2.1] - 2026-01-08

- Added `kirby://uuid/new` resource to generate Kirby UUID strings.
- Linked `kirby://uuid/new` in update-schema guides for pages, files, blocks, and layouts.
- Added unit coverage for UUID resource output.

## [1.2.0] - 2026-01-07

- Dropped prompt-driven setup guidance in favor of Skills.
- Added bundled Codex/Claude Skills and documented how to copy them into the client.

## [1.1.1] - 2026-01-01

- Tiny improvement to the `kb/update-schema/blueprint-file.md` guide.

## [1.1.0] - 2026-01-01

- Updated MCP PHP SDK dependency to `mcp/sdk` v0.2.2.
- Added SIGINT/SIGTERM handling for graceful stdio server shutdown.
- Added Mago tool detection to the composer audit (`carthage-software/mago` or `mago` binary).
- Added `kirby_online_plugins` tool to search the official Kirby plugin directory (plugins.getkirby.com) and optionally fetch plugin details as markdown.
- Added runtime tools (`kirby_read_site_content`, `kirby_read_file_content`, `kirby_read_user_content`, `kirby_update_site_content`, `kirby_update_file_content`, `kirby_update_user_content`) plus resources (`kirby://site/content`, `kirby://file/content/{encodedIdOrUuid}`, `kirby://user/content/{encodedIdOrEmail}`) and blueprint update-schema guides.
- Added KB resources `kirby://kb` and `kirby://kb/{path}` to list and read bundled knowledge base documents.
- Added the Panel development KB (`kb/panel/`) with kirbyup + kirbyuse focus for better extension DX.

## [1.0.2] - 2025-12-21

- Remove composer.lock from composer audit outputs to reduce payload size for init/info tools/resources. thanks @medienbaecker

## [1.0.1] - 2025-12-21

- Fixed CI workflows and minor PHPStan reported errors.

## [1.0.0] - 2025-12-21

- Initial release.
