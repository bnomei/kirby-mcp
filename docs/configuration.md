# Configuration reference

[README](../README.md) · [Remote HTTP setup](remote.md) · [Tools and resources](reference.md) · [Global reference mode](global-reference.md)

## Choose the configuration file

Project configuration lives at `.kirby-mcp/mcp.json`, falling back to `.kirby-mcp/config.json` only when `mcp.json` does not exist. Files are not merged. `install` creates the default file only if neither exists; `update` preserves existing configuration. Use a JSON object. Keep this directory outside the public document root or deny web access: it also holds dumps, OAuth state and other local state.

Environment overrides must reach the process doing the work: the MCP process for stdio, the PHP web process for HTTP. Restart the relevant process after changing its environment.

## Project options

| Option                  | Type           | Default           | Behavior                                                                                                                        |
| ----------------------- | -------------- | ----------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| `cache.ttlSeconds`      | integer        | `60`              | In-memory read caches, including command help, roots inspection and completions. Range 0–3600 seconds; 0 disables caching.      |
| `docs.ttlSeconds`       | integer        | `86400`           | In-memory fetched getkirby.com Markdown cache. Range 0–604800 seconds; 0 disables caching.                                      |
| `cli.allow`             | string array   | `[]`              | Additional read-command allowlist patterns for `kirby_run_cli_command`; supports `*`, e.g. `plugin:*`.                          |
| `cli.allowWrite`        | string array   | `[]`              | Additional write-command allowlist patterns; calls still need `allowWrite=true`.                                                |
| `cli.deny`              | string array   | `[]`              | Always-blocked patterns, including otherwise allowlisted commands.                                                              |
| `dumps.enabled`         | boolean        | `true`            | Allow `mcp_dump()` writes.                                                                                                      |
| `dumps.maxBytes`        | integer        | `2097152`         | Compaction threshold in bytes; see [dump storage](#dump-storage-and-redaction). Non-negative; 0 disables size-based compaction. |
| `dumps.secretPatterns`  | string array   | Built-in patterns | Omit or use `null` for defaults; `[]` disables masking; a supplied list replaces defaults.                                      |
| `ide.typeHintScanBytes` | integer        | `16384`           | Bytes scanned per controller/model for IDE baseline hints; clamped to 1024–1048576.                                             |
| `kirby.host`            | string or null | `null`            | Default `KIRBY_HOST` for wrapped CLI calls, selecting host-specific Kirby config.                                               |
| `eval.enabled`          | boolean        | `false`           | Enable `kirby_eval` / `kirby mcp:eval`; each call still needs confirmation.                                                     |
| `query.enabled`         | boolean        | `true`            | Enable `kirby_query_dot` / `kirby mcp:query:dot`; each call still needs confirmation.                                           |
| `activity.enabled`      | boolean        | `false`           | Opt into the admin-only recent-activity indicator; must be JSON `true`.                                                         |

The CLI tool has a built-in allowlist; these arrays extend or restrict it rather than replacing it. Enabling eval or generic commands grants trusted execution, not a sandbox. See [remote boundaries](remote.md#security-boundaries).

## HTTP options

These options configure transport and authentication; follow [remote setup](remote.md#install-and-register-the-routes) to register Kirby routes and choose credentials. Setting `http.enabled` does not itself register a route or start a listener.

| Option                              | Type           | Default                   | Behavior                                                                                                                                                    |
| ----------------------------------- | -------------- | ------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `http.enabled`                      | boolean        | `false`                   | Enable optional Streamable HTTP.                                                                                                                            |
| `http.host`                         | string         | `127.0.0.1`               | Low-level listener/config validation bind host; shared-token mode requires loopback. Not the Kirby web-server bind address.                                 |
| `http.port`                         | integer        | `8765`                    | Low-level listener port; not used by the Kirby route adapter.                                                                                               |
| `http.path`                         | string         | `/mcp`                    | MCP endpoint; match the first argument to `KirbyMcpRoutes::routes()`.                                                                                       |
| `http.allowedOrigins`               | string array   | `[]`                      | With entries, supplied origins must match exactly; an empty list accepts only loopback origins. Requests without Origin pass this check.                    |
| `http.auth.mode`                    | string or null | `null`                    | Required when enabled: `shared-token`, `remote-token`, or `oauth`.                                                                                          |
| `http.auth.token`                   | string or null | `null`                    | Loopback shared-token secret; prefer environment secret storage.                                                                                            |
| `http.auth.tokens`                  | array          | `[]`                      | Remote-token records: `id`, `hash` (`sha256:<64-hex>`), existing Kirby `userId`, optional `scopes`. Omitted/empty record scopes inherit `http.auth.scopes`. |
| `http.auth.issuer`                  | string or null | `null`                    | Expected OAuth JWT issuer; built-in provider can derive it.                                                                                                 |
| `http.auth.audience`                | string or null | `null`                    | Expected JWT audience/resource, usually the public MCP URL; built-in provider can derive it.                                                                |
| `http.auth.jwksUri`                 | string or null | `null`                    | JWT signature verification JWKS URI; built-in provider can derive it.                                                                                       |
| `http.auth.scopes`                  | string array   | `[]`                      | Scope defaults/issuance policy depending on auth mode; see below and the [scope map](remote.md#scopes).                                                     |
| `http.oauthProvider.enabled`        | boolean        | `false`                   | Built-in authorization server.                                                                                                                              |
| `http.oauthProvider.path`           | string         | `/mcp/oauth`              | Provider prefix; match the helper's `oauthPath` argument.                                                                                                   |
| `http.oauthProvider.consent`        | string         | `snippet`                 | `snippet`, `always`, `remember`, or `auto`; see [OAuth setup](remote.md#built-in-oauth-for-claude-connectors).                                              |
| `http.oauthProvider.consentSnippet` | string         | `kirby-mcp/oauth-consent` | Custom consent snippet name.                                                                                                                                |
| `http.oauthProvider.role`           | string         | `admin`                   | Role admitted to authorize clients, or `*` for any authenticated Kirby user; capability checks still apply.                                                 |

**An empty scope list does not mean “deny all.”** Configure scopes explicitly:

- **Shared token:** `http.auth.scopes` grants the token's scopes; `[]` grants all five.
- **Built-in OAuth:** it controls scopes available for issuance; `[]` makes all five available. It is not a validation-time ceiling on already-issued JWTs.
- **Remote token:** omitted/empty record scopes inherit `http.auth.scopes`; if still empty, the token receives only `kirby-mcp:read`. Explicit record scopes are not intersected with this list.
- **External OAuth:** the signed JWT's `scope` claim grants scopes. Configure the external issuer; `http.auth.scopes` does not limit accepted JWT scopes.

The SSE lifetime is a route-helper argument (`sseMaxSeconds`, default 300), not an MCP JSON option. Session and filesystem subscription limits are described in [remote streaming](remote.md#sessions-streaming-and-subscriptions).

## Environment variables

### Project and execution

| Variable                        | Effect                                                                                                                                       |
| ------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| `KIRBY_MCP_PROJECT_ROOT`        | Override project-root autodetection.                                                                                                         |
| `KIRBY_MCP_KIRBY_BIN`           | Override Kirby CLI binary resolution, normally `vendor/bin/kirby`.                                                                           |
| `KIRBY_MCP_PHP_BINARY`          | PHP CLI executable for wrapped Kirby calls; overrides automatic `PHP_BINARY` / `PHP_BINDIR/php` resolution. Useful under PHP-FPM/FrankenPHP. |
| `KIRBY_MCP_HOST` / `KIRBY_HOST` | Host override, ahead of `kirby.host`; `KIRBY_MCP_HOST` takes precedence. No host is forced by default.                                       |
| `KIRBY_MCP_DUMPS_ENABLED`       | Override `dumps.enabled`.                                                                                                                    |
| `KIRBY_MCP_ENABLE_EVAL`         | Override eval enablement; confirmation remains required.                                                                                     |
| `KIRBY_MCP_ENABLE_QUERY`        | Override query enablement; confirmation remains required.                                                                                    |

### HTTP transport and credentials

| Variable                                        | Configuration/effect                                                                      |
| ----------------------------------------------- | ----------------------------------------------------------------------------------------- |
| `KIRBY_MCP_HTTP_ENABLED`                        | `http.enabled`                                                                            |
| `KIRBY_MCP_HTTP_HOST`                           | `http.host`                                                                               |
| `KIRBY_MCP_HTTP_PORT`                           | `http.port`                                                                               |
| `KIRBY_MCP_HTTP_PATH`                           | `http.path`; also match registered routes.                                                |
| `KIRBY_MCP_HTTP_ALLOWED_ORIGINS`                | Comma-separated `http.allowedOrigins`.                                                    |
| `KIRBY_MCP_HTTP_AUTH_MODE`                      | `http.auth.mode`                                                                          |
| `KIRBY_MCP_HTTP_TOKEN`                          | `http.auth.token` for loopback development.                                               |
| `KIRBY_MCP_HTTP_REMOTE_TOKEN`                   | Raw secret for an additional remote-token record; hashed internally.                      |
| `KIRBY_MCP_HTTP_REMOTE_TOKEN_HASH`              | Precomputed `sha256:<64-hex>` instead of raw secret; raw-token variable takes precedence. |
| `KIRBY_MCP_HTTP_REMOTE_TOKEN_ID`                | Environment record ID; default `env`.                                                     |
| `KIRBY_MCP_HTTP_REMOTE_TOKEN_USER_ID`           | Required existing Kirby user ID for the environment token.                                |
| `KIRBY_MCP_HTTP_REMOTE_TOKEN_SCOPES`            | Comma-separated environment-token scopes; defaults to configured global scopes.           |
| `KIRBY_MCP_HTTP_OAUTH_ISSUER`                   | `http.auth.issuer`                                                                        |
| `KIRBY_MCP_HTTP_OAUTH_AUDIENCE`                 | `http.auth.audience`                                                                      |
| `KIRBY_MCP_HTTP_OAUTH_JWKS_URI`                 | `http.auth.jwksUri`                                                                       |
| `KIRBY_MCP_HTTP_SCOPES`                         | Comma-separated `http.auth.scopes`.                                                       |
| `KIRBY_MCP_HTTP_OAUTH_PROVIDER_ENABLED`         | `http.oauthProvider.enabled`                                                              |
| `KIRBY_MCP_HTTP_OAUTH_PROVIDER_PATH`            | `http.oauthProvider.path`; also match registered routes.                                  |
| `KIRBY_MCP_HTTP_OAUTH_PROVIDER_CONSENT`         | `http.oauthProvider.consent`                                                              |
| `KIRBY_MCP_HTTP_OAUTH_PROVIDER_CONSENT_SNIPPET` | `http.oauthProvider.consentSnippet`                                                       |
| `KIRBY_MCP_HTTP_OAUTH_PROVIDER_ROLE`            | `http.oauthProvider.role`                                                                 |

Boolean overrides accept values such as `1/0`, `true/false`, and `on/off`. HTTP environment settings take precedence over corresponding JSON options; the environment remote token is added to configured token records, not substituted for the entire list. There is no dedicated activity environment override.

`KIRBY_MCP_HTTP_SSE_MAX_SECONDS` sets the low-level `kirby-mcp http` listener's stream lifetime (default 300, clamped to 1–3600 seconds). It does not override the Kirby route helper's `sseMaxSeconds` argument. `KIRBY_MCP_TRACE_ID` and `KIRBY_MCP_RUNTIME_USER_ID` are internal subprocess context passed by MCP operations, not remote identity configuration; remote identity must come from validated credentials.

## Dump storage and redaction

`mcp_dump()` appends JSONL events at `.kirby-mcp/dumps.jsonl`. When a write would exceed `dumps.maxBytes`, the writer starts with the newest half of existing lines and removes more old lines as needed to fit the incoming entry. This is in-place compaction, not a rotated-file archive. An oversized entry is replaced with a small error record if that record fits, otherwise omitted. Setting the limit to 0 disables these size limits.

Default masking scans for sensitive values including common API keys, JWTs, Bearer tokens, passwords and IPs. It reduces accidental exposure; inspect output before sharing it. A custom `dumps.secretPatterns` list replaces the defaults, so include every pattern you need. An empty array disables redaction entirely.

Render with `kirby_render_page`, then pass its `traceId` to `kirby_dump_log_tail`. No output? Check dump enablement in the writing process, filesystem permissions, whether the request actually called `mcp_dump()`, and the trace/path filter. Stateless HTTP does not retain a last-render trace for you.

## Panel activity

After enabling `activity.enabled`, run `vendor/bin/kirby-mcp update` to copy current Panel assets, then reload the Panel. No frontend build or additional secret is needed.

The indicator is visible only to Kirby admins. It represents the last **successful tool call or resource read**, not an open connection, ongoing work or agent count. Initialization, discovery, ping, failed operations and Panel polling do not refresh it; the `kirby_init` tool does.

| Age                               | Appearance   |
| --------------------------------- | ------------ |
| Under 30 seconds                  | Orange       |
| 30 seconds–2 minutes              | Muted orange |
| 2–5 minutes                       | Grey         |
| 5 minutes or more, or no activity | Hidden       |

Hover, focus or tap for elapsed time. The Panel polls every 15 seconds while visible and hides the indicator on authentication/network failure. `GET /api/kirby-mcp/activity` uses native Kirby API authentication and requires an admin; it returns only `state` and `ageSeconds`, not identities, tool names, arguments or content. There is no public endpoint or CSS-embedded token. The adapter uses Kirby's `created` hook and an isolated DOM element rather than replacing core Panel components.

Project-local stdio and HTTP share `.kirby-mcp/activity`; global reference mode never writes it. MCP and web processes must share the same project directory and filesystem permissions. This is a best-effort single-filesystem signal, not an audit log or multi-server presence service.

## Runtime and cache checks

- Run `kirby_runtime_status` if runtime-only tools fail; install/update wrappers using the matching package version.
- Set `KIRBY_MCP_PHP_BINARY` to an executable PHP CLI if the web runtime cannot resolve one.
- Use `kirby_cache_clear` for the current MCP process's in-memory caches, not a site-wide Kirby cache purge.
- Distinguish internal TTLs above from [MCP protocol cache hints](reference.md#cache-and-subscription-semantics). Remote project results remain private and immediately stale to clients.
- If project detection fails, run from the project root, pass `--project=/absolute/path`, or set `KIRBY_MCP_PROJECT_ROOT`. [Global mode](global-reference.md) intentionally does not accept project context.
