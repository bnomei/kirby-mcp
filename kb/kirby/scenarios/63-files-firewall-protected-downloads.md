# Scenario: Protected file downloads (files firewall)

## Goal

Prevent direct access to sensitive files in `content/` and only allow downloads when:

- user is authenticated / authorized
- a token is valid
- a route checks permissions and streams the file

## Inputs to ask for

- Which files need protection (all files vs a subset)
- Authorization rules (roles, ownership, time-limited tokens)
- Whether URLs should be stable or signed/expiring

## Internal tools/resources to use

- Inspect routes: `kirby://config/routes`
- List registered routes (runtime truth): `kirby_routes_index(patternContains='download')` (requires `kirby_runtime_install`)
- Inventory plugins/components: `kirby_plugins_index`, `kirby://extensions`
- Validate flow by rendering protected pages: `kirby_render_page`

## Implementation steps

1. Add a plugin that overrides file URL generation (`file::url`, `file::version`) so protected files link to a route.
2. Add a route that:
   - checks authorization
   - returns the file response (or 403)
3. Ensure protected files aren’t publicly reachable under predictable paths.

## Examples

```php
return [
  'routes' => [
    [
      'pattern' => 'download/(:all)',
      'action'  => function ($path) {
        if (!kirby()->user()) return false;
        return kirby()->file($path);
      }
    ]
  ]
];
```

## Verification

- As a guest, confirm protected URLs are not accessible.
- As an authorized user, confirm downloads work and stream the correct file.
- Confirm the route is registered and locate its definition with `kirby_routes_index(patternContains='download')`.

## Glossary quick refs

- kirby://glossary/files
- kirby://glossary/route
- kirby://glossary/plugin

## Links

- Cookbook: Files firewall: https://getkirby.com/docs/cookbook/security/files-firewall
- Guide: Routing: https://getkirby.com/docs/guide/routing
