# Serve Kirby MCP over HTTP

[README](../README.md) · [Configuration](configuration.md) · [Tool and resource reference](reference.md)

Use Streamable HTTP when the client cannot start a project-local stdio process. HTTP is disabled by default; enabling it does not turn `vendor/bin/kirby-mcp` into a web server. Kirby serves the route through its normal PHP runtime.

## Install and register the routes

Install as a production dependency if the route must survive `composer install --no-dev`:

```bash
composer require bnomei/kirby-mcp
vendor/bin/kirby-mcp install
```

After package upgrades, run `vendor/bin/kirby-mcp update` to refresh runtime wrappers and the small plugin adapter. The implementation and dependencies stay in `vendor/`; the adapter in the resolved plugins root (normally `site/plugins/kirby-mcp`) registers permissions and the optional authenticated Panel activity integration. It does not register public MCP routes.

Merge the route helper into `site/config/config.php`; do not replace existing routes:

```php
<?php

use Bnomei\KirbyMcp\Mcp\KirbyMcpRoutes;

return [
    'routes' => [
        ...KirbyMcpRoutes::routes(),
    ],
];
```

This registers `/mcp` and OAuth discovery/provider routes. No special Nginx location or proxy to the CLI executable is needed. Keep the package autoloadable by the web process. If you customize paths, match both the [MCP configuration](configuration.md#http-options) and the helper arguments:

```php
'routes' => [
    ...KirbyMcpRoutes::routes('/custom-mcp', oauthPath: '/custom-mcp/oauth'),
],
```

The helper returns route definitions at runtime; there is no separately copied route bundle to refresh. A registered MCP route returns 404 while `http.enabled` is false.

For configuration diagnostics, run `vendor/bin/kirby-mcp http --check --json` from the project root. The separate low-level `vendor/bin/kirby-mcp http` listener supports loopback shared-token development only; use the Kirby route for remote-token/OAuth. Its bind host/port and stream-lifetime environment setting are listed in [configuration](configuration.md#http-options).

## Choose authentication

| Mode           | Use                                                                              |
| -------------- | -------------------------------------------------------------------------------- |
| `shared-token` | Same-machine loopback development only.                                          |
| `remote-token` | Public HTTPS clients that can send a Bearer header.                              |
| `oauth`        | Interactive OAuth clients, including Claude Desktop/Claude.ai custom connectors. |

Put the following examples in `.kirby-mcp/mcp.json`. Use narrowly scoped credentials, keep secrets out of source control, and keep `.kirby-mcp/` outside the public document root or deny web access to it. All MCP requests require `Authorization: Bearer …`; query-string credentials are rejected. Non-loopback route requests require HTTPS.

Requests without an `Origin` pass the Origin check. With a nonempty `http.allowedOrigins`, supplied origins must match exactly. When the list is empty, only loopback origins are accepted. Bearer authentication remains required in every case.

### Local loopback token

```json
{
  "http": {
    "enabled": true,
    "allowedOrigins": ["http://127.0.0.1:3000"],
    "auth": {
      "mode": "shared-token",
      "scopes": ["kirby-mcp:read", "kirby-mcp:runtime"]
    }
  }
}
```

Supply the secret as `KIRBY_MCP_HTTP_TOKEN` in the web process environment (or `http.auth.token`). Both PHP's `REMOTE_ADDR` and the request host must be loopback. Accepted loopback hosts are `localhost`, `::1`, and valid IPv4 literals in `127.0.0.0/8`. This is trusted-operator access, not a remote-user permission boundary.

Shared-token HTTP requests on other `127/8` addresses (such as `127.0.0.2`) remain supported, but do not advertise OAuth resource metadata. SDK OAuth URLs allow plain HTTP only on `localhost`, `127.0.0.1`, and `::1`. Invalid request-derived resource URLs return HTTP 400 without stopping the listener.

### Remote bearer token

```json
{
  "http": {
    "enabled": true,
    "auth": {
      "mode": "remote-token",
      "tokens": [
        {
          "id": "editor-client",
          "hash": "sha256:replace-with-64-hex-sha256-hash",
          "userId": "existing-kirby-user-id",
          "scopes": ["kirby-mcp:read", "kirby-mcp:runtime"]
        }
      ]
    }
  }
}
```

Generate a hash locally (avoid leaving real secrets in shell history):

```bash
php -r 'echo "sha256:" . hash("sha256", $argv[1]) . PHP_EOL;' 'replace-with-a-long-random-secret'
```

Send the raw token, not the hash, in the client's Bearer header. Each token must name an existing Kirby user by ID; there is no superuser fallback or automatic account provisioning. Configure that user's [role capabilities](#configure-role-permissions) too.

Alternatively supply an environment token to the web process:

```dotenv
KIRBY_MCP_HTTP_AUTH_MODE=remote-token
KIRBY_MCP_HTTP_REMOTE_TOKEN=replace-with-a-long-random-secret
KIRBY_MCP_HTTP_REMOTE_TOKEN_ID=editor-client
KIRBY_MCP_HTTP_REMOTE_TOKEN_USER_ID=existing-kirby-user-id
KIRBY_MCP_HTTP_REMOTE_TOKEN_SCOPES=kirby-mcp:read,kirby-mcp:runtime
```

`KIRBY_MCP_HTTP_REMOTE_TOKEN_HASH` can replace the raw-token variable. HTTP must still be enabled. CLI clients usually omit Origin; browser/webview clients need their exact origins configured. Use OAuth instead for Claude custom connectors.

### Built-in OAuth for Claude connectors

1. Install/update the runtime adapter and register the route helper above.
2. Enable the following configuration and configure the connecting user's role.
3. Add a Claude custom connector with the MCP endpoint URL, for example `https://example.com/mcp`.
4. Sign in through Kirby's native Panel login and approve the client.

```json
{
  "http": {
    "enabled": true,
    "allowedOrigins": ["https://claude.ai"],
    "auth": {
      "mode": "oauth",
      "scopes": ["kirby-mcp:read", "kirby-mcp:runtime"]
    },
    "oauthProvider": {
      "enabled": true,
      "consent": "snippet",
      "role": "admin"
    }
  }
}
```

No separate OAuth server package is required. The built-in provider derives issuer, audience/resource and JWKS URL from the incoming HTTPS request unless explicitly configured. Provider state is stored in `.kirby-mcp/oauth`, not Kirby cache.

`http.oauthProvider.role` defaults to `admin`. Set an existing role name (such as `editor`) to admit that role, or `"*"` to admit any authenticated Kirby user. This admission setting does not change the user's role or bypass MCP capabilities.

Panel must be enabled. Unauthenticated authorization requests are saved under `.kirby-mcp/oauth/sessions` and resumed after native Panel login, including its configured challenge/2FA handling. The fixed `/mcp/oauth/resume` route restores the request from the same browser's Kirby session; login handoffs expire after ten minutes and a new handoff replaces the previous one in that browser. Resumed requests always require explicit consent.

Consent defaults to `snippet`; without a custom snippet the built-in approval screen is used. Other modes are `always`, `remember`, and `auto`. Use `auto` only for trusted private deployments: it skips explicit consent for already logged-in users, but not resumed login handoffs.

#### Customize the approval screen

Create `site/snippets/kirby-mcp/oauth-consent.php`, or set `http.oauthProvider.consentSnippet` to another snippet name. It receives `client`, `scopes`, `user`, `approveUrl`, `denyUrl`, and `error`:

```php
<?php
$clientName = (string) ($client['client_name'] ?? $client['client_id'] ?? 'OAuth client');
?>
<?php if ($error !== null): ?>
<p><?= esc((string) $error) ?></p>
<?php endif ?>
<form method="post" action="<?= esc((string) $approveUrl, 'attr') ?>">
  <h1>Authorize <?= esc($clientName) ?></h1>
  <p><?= esc((string) $user?->email()) ?></p>
  <ul>
    <?php foreach ($scopes as $scope): ?>
    <li><?= esc((string) $scope) ?></li>
    <?php endforeach ?>
  </ul>
  <input type="hidden" name="csrf" value="<?= esc((string) csrf(), 'attr') ?>">
  <button type="submit" name="approve" value="1">Approve</button>
  <button type="submit" name="deny" value="1" formaction="<?= esc((string) $denyUrl, 'attr') ?>">Deny</button>
</form>
```

Submit POST to the provided URL with Kirby's `csrf()` token and either `approve=1` or `deny=1`. Do not collect passwords in this snippet; native Panel login remains authoritative.

### External OAuth/OIDC issuer

Leave the built-in provider disabled and configure your issuer:

```json
{
  "http": {
    "enabled": true,
    "allowedOrigins": ["https://client.example"],
    "auth": {
      "mode": "oauth",
      "issuer": "https://auth.example.test",
      "audience": "https://example.test/mcp",
      "jwksUri": "https://auth.example.test/.well-known/jwks.json"
    }
  }
}
```

Configure your issuer to grant the intended JWT `scope` claim, for example `kirby-mcp:read kirby-mcp:runtime`. The local `http.auth.scopes` setting does not restrict scopes accepted from external JWTs.

External JWT signatures must use RS256, RS384, or RS512; built-in OAuth uses RS256 only. Omit `jwksUri` to discover the key set from the issuer; an explicit key-set URL and the issuer must use HTTPS except on `localhost`, `127.0.0.1`, or `::1` for development. Key caching is in-process, not a shared persistent cache.

Kirby MCP validates JWT signatures through JWKS, issuer, audience/resource, expiry and operation scopes; it does not run your external authorization server. Set JWT `sub` to the exact ID of an existing Kirby user in this project, not an email or external account ID. Missing, unknown and deleted users are rejected. There is no account mapping or provisioning. Composer supplies the HTTP/JWT libraries used by Kirby MCP; `league/oauth2-server` is not required by the built-in provider.

## Scopes

| Scope               | Operations                                                                                        |
| ------------------- | ------------------------------------------------------------------------------------------------- |
| `kirby-mcp:read`    | Read-only tools and reference resources.                                                          |
| `kirby-mcp:runtime` | Runtime inspection through Kirby CLI wrappers, including project content resources/subscriptions. |
| `kirby-mcp:write`   | Content/file/user/site mutations; confirmation still required.                                    |
| `kirby-mcp:execute` | Query/eval operations; enablement and confirmation still apply.                                   |
| `kirby-mcp:admin`   | Administrative runtime actions.                                                                   |

Scopes are coarse access gates, not replacements for Kirby permissions or confirmation. Add write, execute or admin only when needed.

## Configure role permissions

OAuth and remote-token connections apply Kirby's registered `bnomei.kirby-mcp` capability map to discovery and each operation. The installed adapter must be loaded. Permission enforcement cannot be disabled for these remote connections. Local stdio and loopback shared-token access remain trusted-operator access.

Define roles in `site/blueprints/users/<role>.yml` using [Kirby's permission configuration](https://getkirby.com/docs/guide/users/permissions):

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

Every ancestor and leaf must be true. `content: false` blocks the branch; `content.pages: true` alone does not grant reads, updates or rendering. `access` is required to connect. All entries default to false for custom roles; unknown/unmapped capabilities are denied. Kirby's native `admin` role remains unrestricted. Use a custom role to restrict access. See also Kirby's [custom permission extension](https://getkirby.com/docs/reference/plugins/extensions/permissions).

### Complete capability map

Tool names omit the `kirby_` prefix. Every intermediate dotted prefix is also a registered permission.

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

## Security boundaries

Remote discovery, `kirby://tools`, and tool suggestions show only permitted capabilities. Calls, reads, completions and subscriptions are independently checked; knowing a hidden name or URI does not grant access. User identity comes from the validated token, never MCP arguments, request metadata or session state. Identity, role and account revocation are rechecked on every HTTP request. An already open stream is not continuously re-authorized.

**Capability gating is not per-target isolation.** A permitted read can access every target supported by that capability. The four dedicated content-update tools additionally execute as the Kirby user, applying native mutation checks and hooks; use blueprint `options` and `before` hooks for project-specific rules. Confirmation remains required. Enabled and permitted eval or generic CLI execution is trusted execution, not a sandbox, and can bypass other permissions. Do not use this server to isolate untrusted users to a private subset of a site.

Origin and bearer validation occur before MCP side effects. HTTP exposes the configured MCP endpoint, not an arbitrary command router. The optional Panel activity API has separate native Kirby admin authentication; see [activity behavior](configuration.md#panel-activity).

Since 6.1.0, CORS preflight (`OPTIONS`) succeeds without a bearer token after Origin and query-credential checks. Actual MCP requests still require bearer authentication and operation scopes.

OAuth protected-resource metadata now follows the resource URL path: `https://example.test/mcp` advertises `https://example.test/.well-known/oauth-protected-resource/mcp`. `KirbyMcpRoutes::routes()` handles this automatically. If you copied route arrays manually, refresh the metadata route to `.well-known/oauth-protected-resource/(:all?)`; the handler only serves the exact configured metadata path and query.

## Sessions, streaming and subscriptions

The server uses MCP SDK v0.9. Existing clients negotiate stateful sessions through `initialize`; protocol `2026-07-28` uses stateless requests over HTTP and stdio. HTTP tools do not require a preceding `kirby_init`, though it remains useful for audit and guidance. See [reference semantics](reference.md#confirmations-and-resource-links) for confirmations and resource links.

Modern resource subscriptions use a bounded 120-second local-filesystem notification bus at `.kirby-mcp/http-notifications`, allowing separate same-host workers to communicate. It is not multi-host/NFS storage and does not promise durability or exactly-once delivery. Each URI requires the scope needed to read it; project content subscriptions require `kirby-mcp:runtime`. Legacy GET/SSE streams recheck every stored subscription against the current token's scopes when opened, independently of role checks.

Stateless dump calls must pass the render `traceId` or request `path`; only handshake sessions provide last-trace convenience. Successful content writes notify subscribed content URIs, not arbitrary filesystem changes.

SSE GET streams default to 300 seconds. `KirbyMcpRoutes::routes(sseMaxSeconds: 300)` controls this bound; it is not a JSON configuration key. The transport asks PHP to extend its execution deadline slightly beyond the stream lifetime. If `set_time_limit()` is disabled, configure PHP/FrankenPHP `max_execution_time` above that lifetime.

## Troubleshoot HTTP

| Symptom                                     | Check                                                                                                                                                           |
| ------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 404 at `/mcp`                               | Route helper registration, matching `http.path`, `http.enabled=true`, web-process configuration and package autoloading.                                        |
| 401/403                                     | Bearer header, expiry/signature, issuer, audience/resource, scopes, exact Origin, existing Kirby user, `access` and all ancestor/leaf role permissions.         |
| Shared token rejected                       | Both request host and `REMOTE_ADDR` must be loopback; do not use it for public routes.                                                                          |
| Claude connector cannot connect             | Use the MCP URL, not provider URL; enable OAuth and the built-in provider, register routes, allow the client Origin and serve non-loopback requests over HTTPS. |
| Login cannot resume                         | Panel must be enabled; use the same browser session and restart an expired/replaced handoff.                                                                    |
| Runtime calls fail under PHP-FPM/FrankenPHP | Install an executable PHP CLI or set `KIRBY_MCP_PHP_BINARY` in the web process environment. Check `kirby_runtime_status`.                                       |
| SSE closes early                            | Check PHP execution deadlines as above and hosting/proxy stream limits.                                                                                         |

Diagnostics go to stderr; MCP logging requests are not advertised. Modern requests can supply valid W3C v00 `traceparent`, including the native HTTP header in browser clients. Diagnostics include trace context, never `tracestate` or `baggage`.

Claude Code and Claude Desktop/Claude.ai connectors are the primary integration targets. Other clients need compatible remote HTTP MCP and authentication support. OpenAI/ChatGPT uses Responses API tools or ChatGPT Apps/MCP Apps rather than the Claude connector flow. External integrations, third-party dependencies, HTTP Accept/cache behavior, and enrolled authenticator/2FA flows are not comprehensively integration-certified; native login support is not a certification of every configured login method.
