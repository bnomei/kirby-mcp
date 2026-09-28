# Changelog

All notable changes to this project will be documented in this file.
The format is based on Keep a Changelog and this project adheres to Semantic Versioning.

## [Unreleased]

- Keep permission-filtered tool-index reads private and non-cacheable, and enforce resource bearer scopes on legacy subscribe/unsubscribe and GET/SSE requests.
- Added a tiny installed Kirby plugin adapter that registers the hierarchical, deny-by-default `bnomei.kirby-mcp` permission map without registering routes or Panel UI; install/update now report its copied `index.php` and generated `composer.json`, and update refreshes the metadata.
- Apply that capability map to OAuth and remote-token discovery and operations. Remote tokens now require an existing Kirby `userId` (`KIRBY_MCP_HTTP_REMOTE_TOKEN_USER_ID` for the environment token); dedicated content updates run as the resolved user and retain Kirby's native mutation checks and hooks. Local stdio and loopback shared-token access remain trusted.

## [1.12.2] - 2026-09-22

- Allowed authenticated HTTP clients to call tools without `kirby_init`, while retaining the init prerequisite for stdio handshake sessions and all bearer/OAuth scope checks.
- Fixed runtime content output contracts: `fieldSchemas` and empty Composer script maps now serialize as JSON objects, and user email may be null.
- Extended PHP's execution deadline around bounded HTTP SSE streams so the configured package lifetime remains authoritative under FrankenPHP and similar SAPIs.
- Added PHP CLI fallback resolution for web SAPIs with an empty `PHP_BINARY`, and restored Kirby-compatible `e()` behavior in CLI-rendered templates.
- Updated the development lock file and Starterkit validation baseline to Kirby 5.6.0, and refreshed Guzzle to patched 7.x releases.

## [1.12.1] - 2026-08-30

- Updated the MCP PHP SDK dependency to `mcp/sdk` v0.8.1, fixing variadic tool and prompt arguments and suppressing spurious list-change notifications during initial registry loading.

## [1.12.0] - 2026-08-25

- Enabled MCP SDK v0.8 dual-era dispatch, preserving stateful handshake clients while adding stateless `2026-07-28` requests.
- Added conservative MCP cache hints: global-reference discovery and bundled static resource reads are public for one hour, while project, runtime, fetched-doc, session, auth, user, and mutable results retain private zero-TTL defaults.
- Added operation-bound modern confirmation elicitation and bounded cross-process local HTTP resource-update notifications.
- Stopped advertising the deprecated MCP logging capability and removed project-owned session log levels; server diagnostics continue to use stderr.
- Added wire-level regression coverage for protocol negotiation, v0.8 invalid-params not-found errors, malformed-request null IDs, and public tool schemas.
- No top-level list-shaped structured tool result is currently exposed, so the v0.8 list-shaped structured-content wire behavior is not applicable yet.
- Added navigable resource links to modern tool results that already expose concrete `kirby://` URIs in structured fields; existing structured data and JSON text remain unchanged.
- Correlated stderr diagnostics with valid modern W3C v00 `traceparent` values without logging `tracestate` or `baggage`.
- Hardened modern subscriptions by enforcing each resource URI's bearer scope, allowing browser `Traceparent` preflights, and using portable zero-based notification cursors.

## [1.11.0] - 2026-07-15

- Updated the MCP PHP SDK dependency to `mcp/sdk` v0.7.0, including hardened malformed JSON-RPC parsing and session-header handling.
- Added HTTP regression coverage for malformed JSON-RPC requests.
- Refreshed compatible Composer dependencies, including Kirby CMS 5.5.2, Kirby CLI 1.10.0, Guzzle PSR-7 2.12.5, and Pest 4.7.5.

## [1.10.2] - 2026-06-29

- Fixed copied Kirby MCP and OAuth route closures so Kirby 5.4.x can bind route actions before invocation. thanks @dfsoeten
- Fixed `kirby_ide_helpers_status` template/snippet PHPDoc warnings so `@var` hints are required only for used `$kirby`, `$site`, and `$page` globals. thanks @sigtrygg-space

## [1.10.1] - 2026-06-29

- Added `KIRBY_MCP_PHP_BINARY` to override the PHP executable used when Kirby CLI calls are wrapped with MCP's prepend file, useful for PHP-FPM deployments where `PHP_BINARY` is not the CLI binary.

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
