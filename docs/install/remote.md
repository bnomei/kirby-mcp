# Install a remote Kirby MCP endpoint

[Installation tutorials](index.md) · [HTTP reference](../remote.md) · [Configuration](../configuration.md)

This walkthrough is for the person operating the Kirby site. It produces an HTTPS MCP endpoint with browser-based Kirby login and a restricted read-only account. People connecting their assistant need the resulting URL and their own authorized Kirby account; they do not need Composer on their computer.

## 1. Check the hosting requirements

- Use a Composer-based Kirby 6 project, PHP 8.3+, and a working Panel login. The verified CMS baseline is **6.0.0-beta.1**: follow the [version warning](../../README.md#quickstart) and start on a test copy.
- Serve the site over HTTPS with a trusted certificate. The MCP client must be able to reach it. For cloud clients, browser access from your laptop does not prove reachability from the provider's servers.
- Keep PHP CLI available to the web process. Runtime tools invoke CLI wrappers; configure `KIRBY_MCP_PHP_BINARY` in the web environment if the correct executable is not on its PATH.
- Allow PHP to write `.kirby-mcp/`, including OAuth state, without making it publicly readable. Keep it outside the document root or explicitly deny web access. Preserve OAuth state across deployments; do not treat it as disposable cache.
- Preserve the request's HTTPS scheme and host through your hosting/proxy setup. The provider derives public OAuth URLs from the incoming request unless configured otherwise.

This guide uses a site rooted at `https://example.com` and the default `/mcp` path. Replace the hostname throughout. Custom paths and external OAuth providers belong in the [HTTP reference](../remote.md).

## 2. Install the package as a production dependency

Run from the Kirby project root:

```bash
composer require bnomei/kirby-mcp:"^6.0"
vendor/bin/kirby-mcp install
```

Do not use `--dev` for a deployment that runs `composer install --no-dev`. Deploy the resulting Composer files and installed runtime adapter using your normal process. After later package upgrades, run `vendor/bin/kirby-mcp update` too.

## 3. Register the HTTP routes

Merge the helper into the site's existing `site/config/config.php`. This minimal example is for a config file without other settings; retain your existing settings and routes when adding it:

```php
<?php

use Bnomei\KirbyMcp\Mcp\KirbyMcpRoutes;

return [
    'routes' => [
        ...KirbyMcpRoutes::routes(),
    ],
];
```

Keep the helper's routes reachable before any catch-all route that would intercept them. Kirby handles MCP and OAuth through its normal web runtime. Do not proxy to `vendor/bin/kirby-mcp` or run the CLI `http` listener for this deployment.

The endpoint is `https://example.com/mcp`. It is neither the Panel URL nor the OAuth authorization URL. Registration alone leaves it disabled until step 5.

## 4. Create a restricted Kirby role and account

Create `site/blueprints/users/mcp-reader.yml`:

```yaml
title: MCP Reader
permissions:
  bnomei.kirby-mcp:
    '*': false
    access: true
    discovery: true
    project: true
    project.info: true
    content: true
    content.pages: true
    content.pages.read: true
    reference: true
    reference.knowledge: true
```

In the Panel, create or select a user and assign the `mcp-reader` role. Review that role's normal Kirby permissions separately: this example limits MCP capabilities, not everything the account can do in the Panel. Give each person their own account rather than sharing an administrator login.

This role allows initialization/project information, page-content reads, tool suggestions, and bundled knowledge search. It intentionally omits writes, execution, maintenance, and most project inspection. Every ancestor of a dotted capability must be enabled; enabling `content.pages` alone is insufficient.

**Read access is not page-level isolation.** This role can read any page supported by the page-content tool. Do not use it to separate mutually untrusted readers by page. Consult the [permission boundaries](../remote.md#security-boundaries) before exposing sensitive content.

## 5. Enable the built-in OAuth provider

Merge this `http` section into `.kirby-mcp/mcp.json`, preserving unrelated settings:

```json
{
  "http": {
    "enabled": true,
    "allowedOrigins": [],
    "auth": {
      "mode": "oauth",
      "scopes": ["kirby-mcp:read", "kirby-mcp:runtime"]
    },
    "oauthProvider": {
      "enabled": true,
      "consent": "snippet",
      "role": "mcp-reader"
    }
  }
}
```

The role name must match step 4. It is an admission rule: an administrator account does not automatically qualify when you explicitly select `mcp-reader`. The built-in approval form works without a custom snippet. Keep explicit consent enabled.

`allowedOrigins` controls requests that carry an `Origin` header; it is not a list of allowed MCP servers or OAuth callback URLs. Requests without that header pass the Origin check. With the empty list above, non-loopback origins are rejected. If your client sends an Origin, add its exact trusted value, for example `https://claude.ai` for a request carrying that Origin. Do not use `*` or add unrelated domains to silence errors. See [HTTP authentication and Origin rules](../remote.md#choose-authentication).

The built-in provider supports **dynamic client registration (DCR)**. Select automatic registration if a client offers a choice. It does not resolve published client-ID metadata documents (CIMD). Do not supply an invented OAuth client ID or secret.

## 6. Check configuration and public discovery

Run on the deployed project, with the deployment's environment:

```bash
vendor/bin/kirby-mcp http --check --json
```

Resolve reported configuration errors before proceeding. This is a configuration check, not proof that the web process uses the same environment or that a cloud client can reach it.

From a machine outside the site's private network, check these default URLs:

```bash
curl -i https://example.com/.well-known/oauth-protected-resource/mcp
curl -i https://example.com/.well-known/oauth-authorization-server
curl -i https://example.com/mcp
```

Expected results:

| Request                          | Expected evidence                                                                    |
| -------------------------------- | ------------------------------------------------------------------------------------ |
| Protected-resource metadata      | HTTP 200 with JSON identifying the MCP resource and authorization server             |
| Authorization-server metadata    | HTTP 200 with JSON containing HTTPS authorization, token, and registration endpoints |
| MCP endpoint without credentials | HTTP 401; authentication is required, so this is not a failed installation           |

An HTML login page, CDN challenge, or site error at a metadata URL is not a successful discovery response. Never put credentials in the URL. These checks do not log a user in or prove tool execution.

## 7. Connect a client and verify a read

Give the user `https://example.com/mcp` and point them to [Claude](claude.md), [ChatGPT](chatgpt.md), [Langdock](langdock.md), or another [client tutorial](index.md). They should authenticate as the account from step 4 and approve only the expected connection.

Run the [page-title check](index.md#check-the-connection-before-asking-for-changes) and compare the result with the Panel. The client will see only role-permitted tools, not the whole README inventory. Scopes authorize operations but do not by themselves filter the tool list.

## Add write access only when needed

1. Test on a non-production copy and decide which exact content operations the account needs.
2. Add `kirby-mcp:write` to the OAuth scope list without removing the read/runtime scopes.
3. Enable the matching role capability, such as `content.pages.update`, and configure Kirby's native mutation permissions and any project hooks. Use the [complete capability map](../remote.md#complete-capability-map).
4. Register a fresh client connection and authorize the expanded scopes. Existing client registrations and tokens do not automatically acquire new scopes. A simple token refresh cannot expand the originally registered grant.
5. Refresh the client's tool list, retain client-side approval for changes, and test one deliberate edit on a disposable page. Read the page back to verify the result.

Kirby MCP's confirmation is separate from the client's approval dialog. Depending on protocol support, a tool can request confirmation through MCP or return a preview for a later explicit `confirm=true` call. Review the intended change before authorizing it. A confirmed update changes stored content; there is no additional MCP publication queue. See [confirmation semantics](../reference.md#confirmations-and-resource-links).

Do not add execute/admin scopes or enable PHP eval merely to make content editing work.

## Recover or disable access

| Symptom                                       | Next check                                                                                                                  |
| --------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| `/mcp` returns 404                            | Package autoloading, route order, `http.enabled`, and the configuration used by the web process                             |
| Discovery works but OAuth says unknown client | Select DCR/automatic registration rather than published identity; recreate the client connection                            |
| Login succeeds but consent is denied          | The account must have the admitted role and its `access` capability                                                         |
| Login handoff expired                         | Start a new connection attempt and finish in the same browser session within ten minutes; avoid simultaneous login attempts |
| Connection works but a tool is absent         | Check the role's full ancestor/leaf capability chain, then refresh client discovery                                         |
| Tool call returns `insufficient_scope`        | Check the token's grant; register/authorize again if scopes were expanded                                                   |
| HTTP 403 with an Origin error                 | Allow only the exact expected Origin sent by this client                                                                    |
| Runtime read fails                            | PHP CLI availability, installed wrappers, filesystem access, and correct project/host configuration                         |

Removing an app from a client is not the same as invalidating every server credential. To stop a custom role's MCP access, set its `access` capability to false; this affects all users of that role. User identity and role permissions are rechecked on each HTTP request, although an already open stream is not continuously re-authorized. For a compromised account, apply your site's account-access response as well. Set `http.enabled` to false to disable the endpoint for everyone.

For bearer deployments, remove/rotate the corresponding server token record. Do not delete all OAuth state as a routine disconnect action: it affects other connections and signing keys. The current provider has no documented per-connection management UI. See the [HTTP reference](../remote.md) for bearer setup, external issuers, streaming, and detailed security limits.
