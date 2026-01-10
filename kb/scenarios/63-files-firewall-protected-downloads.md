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
- Whether protected files are tagged via a file template (e.g. `protected`)
- Whether the server blocks direct access to `/content` (rewrite rules)

## Internal tools/resources to use

- Inspect routes: `kirby://config/routes`
- List registered routes (runtime truth): `kirby_routes_index(patternContains='download')` (requires `kirby_runtime_install`)
- Inventory plugins/components: `kirby_plugins_index`, `kirby://extensions`
- Validate flow by rendering protected pages: `kirby_render_page`

## Implementation steps

1. Tag protected files (e.g. files section with `template: protected`).
2. Add a plugin that overrides `file::url` so protected files link to a route.
3. Add a route that checks authorization and returns `$file->download()` (or error page).
4. Override `file::version` so Panel thumbs don’t leak protected images to `media/`.
5. Ensure protected files aren’t reachable via direct `/content` paths (server rewrite rules).

## Examples

```php
return [
  'routes' => [
    [
      'pattern' => 'downloads/(:any)',
      'action'  => function ($filename) {
        if (!kirby()->user()) {
          return site()->errorPage();
        }

        if (($page = page('downloads')) && $file = $page->files()->findBy('filename', $filename)) {
          return $file->download();
        }

        return site()->errorPage();
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
