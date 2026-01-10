# Scenario: Headless API with KQL (`/api/query`)

## Goal

Expose Kirby content as JSON for a headless frontend by using:

- Kirby API
- the KQL plugin for flexible queries (`/api/query`)
- Basic Auth for authentication

## Inputs to ask for

- Which consumers will call the API (frontend app, integration, automation)
- Auth requirements (Basic Auth vs session, dedicated API user)
- Which data needs to be exposed (pages, files, users; public vs private)
- Performance/security constraints (rate limiting, caching, query scope)
- Whether HTTPS is available (Basic Auth requires HTTPS unless `allowInsecure` is set)

## Internal tools/resources to use

- Confirm environment + Kirby version: `kirby://info`
- Inspect config: `kirby://config/api`
- Inventory plugins (to confirm KQL is installed): `kirby_plugins_index`

## Implementation steps

1. Install the KQL plugin (project dependency).
2. Enable Basic Auth for the API in `site/config/config.php`.
   - Use HTTPS (or `allowInsecure` for local testing only)
3. Create a dedicated API user account (avoid breaking integrations when changing your own password).
4. Consider a KQL allowlist/blocked methods config to reduce exposure.
5. Query `/api/query` via POST with Basic Auth.

## Examples (from the cookbook recipe)

### Enable API Basic Auth

`site/config/config.php`

```php
return [
  'api' => [
    'basicAuth' => true,
  ]
];
```

### Example KQL query

Use `page('notes').children` to fetch note subpages:

```
page('notes').children
```

POST JSON body:

```json
{ "query": "page('notes').children" }
```

## Verification

- Request `POST /api/query` with Basic Auth from an API client (curl/Insomnia).
- Confirm unauthenticated requests return `403 Unauthenticated`.

## Glossary quick refs

- kirby://glossary/api
- kirby://glossary/kql
- kirby://glossary/plugin
- kirby://glossary/request

## Links

- Cookbook: Headless getting started: https://getkirby.com/docs/cookbook/headless/headless-getting-started
- KQL plugin: https://github.com/getkirby/kql
- Guide: API: https://getkirby.com/docs/guide/api
- Guide: API authentication: https://getkirby.com/docs/guide/api/authentication
