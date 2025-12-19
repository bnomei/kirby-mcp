# Route (aliases: “Kirby route”, `routes` extension, `config.php` routes)

## Meaning

A route maps a URL pattern to an action. Routes can return pages, redirects, JSON, virtual pages, or any supported response type.

Routes can be defined in config or registered via the `routes` plugin extension.

## In prompts (what it usually implies)

- “Create a custom endpoint” often means: add a route that returns JSON or a custom page render.
- “Pretty URLs / remove parent slug” often means: custom routing logic.
- “Virtual pages” often means: route returns a `Page` object that doesn’t exist on disk.

## Variants / aliases

- Registered in config (e.g. `site/config/config.php`)
- Registered in a plugin via `Kirby::plugin([... 'routes' => [ ... ]])`
- A route has a `pattern` and an `action` (and optional language scope, methods, etc.)

## Example

```php
Kirby::plugin('acme/routes', [
    'routes' => [
        [
            'pattern' => 'my/awesome/url',
            'action'  => function () {
                return 'Hello';
            }
        ]
    ]
]);
```

## MCP: Inspect/verify

- Use `kirby://extension/routes` to see the official “routes extension” shape and examples.
- Use `kirby_online` for patterns/response types (“router patterns”, “response types”, “virtual pages”).
- Inspect config-defined routes via `kirby://config/routes` (requires `kirby_runtime_install`; does not include plugin-registered routes).
- List registered routes (pattern/method + best-effort source file) via `kirby_routes_index` (requires `kirby_runtime_install`).
  - example: `kirby_routes_index(patternContains='sitemap', method='GET')`
  - open the returned `source.relativePath` (and `action.startLine` if present) to jump to the route definition
  - Tip: paginate with `limit`/`cursor` to avoid truncation.
- To inspect what happens for a specific URL during a request, add a temporary `mcp_dump()` inside the route action, reproduce the URL, then read it via `kirby_dump_log_tail(path='/…')` (or `traceId` from `kirby_render_page`).

## Related terms

- kirby://glossary/plugin
- kirby://glossary/hook
- kirby://glossary/request
- kirby://glossary/page
- kirby://glossary/content-representation

## Links

- https://getkirby.com/docs/guide/routing
- https://getkirby.com/docs/reference/plugins/extensions/routes
- https://getkirby.com/docs/reference/router/patterns
- https://getkirby.com/docs/reference/router/responses
