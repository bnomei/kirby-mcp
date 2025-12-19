# Scenario: Add custom routes (redirects, virtual pages, JSON endpoints)

## Goal

Use Kirby routes to:

- implement redirects (`go()`)
- create virtual utility pages (sitemap, feeds)
- add custom API endpoints
- filter by URL patterns (instead of params)

## Inputs to ask for

- Desired URL pattern and dynamic parts
- Allowed HTTP methods (`GET` only vs `GET|POST|...`)
- Response type (page/file/string/array/Response/redirect)
- Multi-language behavior (default only vs scoped to language(s))

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Read config option (when runtime is installed): `kirby://config/routes`
- List registered routes (runtime truth): `kirby_routes_index` (requires `kirby_runtime_install`)
- Use `kirby_render_page` to validate pages/templates that the route returns (note: it doesn’t execute the router itself)
- When unsure what exists, read `kirby://commands` / use `kirby_run_cli_command` as last resort

## Implementation steps

1. Define routes in `site/config/config.php` or in a plugin (`Kirby::plugin(..., ['routes' => [...]])`).
2. Choose safe patterns:
   - avoid greedy placeholders like `(:any)` if they accidentally catch `.json` representations
3. Add `method` if you need POST/PUT/etc.
4. For multi-language sites:
   - use `language: '*'` or specific language codes
   - consider `site()->visit('some/page', 'en')` in actions
5. Return the right response type:
   - redirect: `return go(...)`
   - JSON: return an array (Kirby will respond with JSON) or return a `Response` object

## Examples

### Basic route in config

```php
<?php

return [
  'routes' => [
    [
      'pattern' => 'my/awesome/url',
      'action'  => function () {
        return 'Hello';
      }
    ],
  ]
];
```

### Redirect with `go()`

```php
return [
  'routes' => [
    [
      'pattern' => 'old-path',
      'action'  => fn () => go('new-path', 301),
    ],
  ],
];
```

### Limit methods

```php
[
  'pattern' => 'webhook',
  'method'  => 'POST',
  'action'  => function () {
    return ['ok' => true];
  }
]
```

### Language-scoped route (multi-language)

```php
return [
  'routes' => [
    [
      'pattern'  => 'test/(:alphanum)',
      'language' => 'en',
      'action'   => function ($language, $slug) {
        if (page($slug)) return $this->next();
        if ($page = page('notes/' . $slug)) return $page;
        return false;
      }
    ],
  ]
];
```

## Verification

- Call the route URL and confirm it returns the expected output/redirect/JSON.
- Confirm it doesn’t shadow real pages or content representations (`.json`, `.rss`, …).
- Confirm the route is registered and locate its definition with `kirby_routes_index(patternContains='…')` (open `source.relativePath`).

## Glossary quick refs

- kirby://glossary/route
- kirby://glossary/language
- kirby://glossary/roots
- kirby://glossary/slug

## Links

- Guide: Routes: https://getkirby.com/docs/guide/routing
- Reference: Route patterns: https://getkirby.com/docs/reference/router/patterns
- Reference: `go()` helper: https://getkirby.com/docs/reference/templates/helpers/go
- Quicktip: Redirects: https://getkirby.com/docs/quicktips/redirects
