# Changelog

All notable changes to this project will be documented in this file.
The format is based on Keep a Changelog and this project adheres to Semantic Versioning.

## [Unreleased]

- Add opt-in `customTools` definitions for project/plugin Kirby CLI commands, with schema-validated inputs, tool discovery and suggestions, existing CLI allow/deny enforcement, write opt-in and HTTP capability/scope checks. Document command paths, results and reuse with Janitor without adding a job system.
- Reject noncanonical CLI command names in help resources, execution tools, policy checks and the runner to prevent command-path traversal and alternate-spelling deny bypasses.
- Enforce HTTPS for non-loopback OAuth MCP requests, cap built-in OAuth grants by current provider/client scopes, and revoke refresh-token families on replay under a per-family lock. Existing refresh tokens without family state require fresh authorization; already-issued access tokens retain their normal expiry.
- Add native paginated site/page children and attachment browsing with UUID-preferred resource links, original text/blob reads, and bounded MCP image previews. Existing `content` APIs remain metadata-only.
- Add confirmed local-copy uploads and authenticated, user-bound single-use remote upload tickets, with blueprint contexts, native create/accept checks and collision rejection. Add confirmed native page moves preserving UUID identity.
- Register the HTTP upload route and explicit runtime/write scopes and Kirby capability permissions for these operations. Refresh runtime installation and any manually copied HTTP routes after upgrading.

## [6.1.0] - 2026-10-18

- Upgrade to MCP PHP SDK v0.9.0 for Kirby 6, including concurrent-request isolation fixes, stricter structured tool output validation, and modern `2026-07-28` support over stdio. PHP 8.3+ and the Kirby 6 baseline remain unchanged; no Kirby 5 backport.
- Migrate bearer scopes and Kirby user identity to the SDK's validated access-token API; update external JWKS discovery and built-in OAuth signature validation while retaining operation scopes and Kirby permissions.
- Allow external OAuth issuer discovery when `jwksUri` is omitted and explicitly support RS256/RS384/RS512 signatures; built-in OAuth remains RS256-only. Reject malformed HTTP authorities and invalid resource URLs without terminating the listener; retain shared-token access on valid 127/8 addresses without inventing OAuth metadata URLs.
- Serve OAuth protected-resource metadata at the resource-specific RFC 9728 URL (for example, `/.well-known/oauth-protected-resource/mcp`). Refresh manually copied metadata routes; installations using `KirbyMcpRoutes::routes()` pick up the new route automatically.
- Allow unauthenticated CORS preflight after Origin and query-credential checks; actual MCP requests still require bearer authentication. Update completion-provider metadata and regression coverage for modern stdio, confirmation binding, and streamed resource notifications.

## [6.0.1] - 2026-10-15

- Reorganize the README around capabilities, local setup, and everyday usage, with grouped tool/resource lists and a dedicated introduction to the bundled knowledge base.
- Consolidate detailed configuration, remote authentication/permissions, and global reference setup into focused guides; replace the migration ledger with current compatibility notes.
- Add a cropped Panel activity screenshot and clarify default settings, client executable paths, and authentication configuration.
- Exclude documentation, images, and orb development files from Composer distribution archives while retaining runtime assets, the knowledge base, and skills.

## [6.0.0] - 2026-10-15

- Target Kirby 6 (verified CMS baseline 6.0.0-beta.1) and require PHP 8.3+. Kirby 5 projects should install MCP `^1.15`; Kirby 7+ is excluded. The stable MCP version does not certify the upstream CMS beta for production.
- Pin kit fixtures and Composer to the same Kirby release, resolve dependencies for PHP 8.3, and test both PHP 8.3 and 8.5 in CI.
- Reject unsupported project runtimes before dispatching project-local wrappers, including older MCP installations, while preserving reference access; use Kirby 6 Panel URLs, blueprint namespace, and UUID string resolution.
- Distinguish value-bearing picker fields from display fields in content schemas. Generate update-schema links only for known value fields with bundled guides; preserve existing built-in section support via automatic conversion.
- Migrate the bundled KB and all 15 skills to canonical Kirby 6 guidance, including picker storage, blueprint references, Vue 3 fields, authentication, live editor, core PHP/storage/HTTP contracts and an actionable Kirby 5 migration guide.
- Fix portable fixture temporary-file creation and document TXT dashed-key normalization. See [compatibility notes](docs/reference.md#compatibility-and-reference-limits) for upstream Starterkit asset casing and [remote integration limits](docs/remote.md#troubleshoot-http).

## [1.15.1] - 2026-10-15

- Refreshed the bundled Kirby 5 knowledge base against documentation updates and stable releases through Kirby 5.6.1, preserving existing Kirby 6 notes without adding a migration guide.
- Corrected link and entries field storage/update examples, and made the content cleanup recipe language-fallback-safe while preserving saved content and required fields.
- Clarified permissions, query runners, CORS, preview routing, content versions, plugin assets, and Panel contracts; recorded the maintenance baseline for future documentation passes.

## [1.15.0] - 2026-10-08

- Replaced the built-in OAuth credential form with native Kirby Panel login, preserving configured login methods and two-factor authentication. Login returns through `/mcp/oauth/resume`; installations with copied route definitions must refresh their OAuth routes.
- Bound pending OAuth handoffs to the initiating browser session with ten-minute expiry, explicit post-login consent, and atomic single-use consumption. Starting another handoff in the same browser replaces the previous one.
- Added anti-framing headers to built-in and custom-snippet consent pages, and rejected deceptive `127.*` public hostnames from the OAuth provider's loopback HTTP exemption.

## [1.14.0] - 2026-09-28

- Added an opt-in, admin-only Panel robot indicator for recent successful MCP tool/resource activity. It fades from orange to grey and disappears after five minutes, using authenticated polling and a single shared timestamp rather than session tracking.

## [1.13.0] - 2026-09-28

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
