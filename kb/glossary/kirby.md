# Kirby (aliases: `$kirby`, `Kirby\Cms\App`, `kirby()`)

## Meaning

In Kirby, “Kirby” often refers to the **application/runtime object** (`Kirby\Cms\App`). It is the central access point for runtime state like roots, options, routes, users, plugins, request/response handling, etc.

In templates/snippets/controllers, Kirby exposes this object as the `$kirby` variable. Outside of that context, you can fetch it with the `kirby()` helper.

## In prompts (what it usually implies)

- “Get the Kirby instance / `$kirby`” means: you need the runtime object to inspect configuration, roots, routes, etc.
- “Where is X located?” usually means: ask Kirby for its **roots** instead of assuming filesystem paths.
- “Why is a helper missing?” can mean: global helper functions were disabled via constants (e.g. `KIRBY_HELPER_DUMP`).

## Variants / aliases

- `$kirby` (available in templates/snippets/controllers)
- `kirby()` helper (fetch `$kirby` anywhere)
- `Kirby\Cms\App` (class name)
- Commonly used related calls:
  - `$kirby->roots()`, `$kirby->root('templates')`
  - `$kirby->option('debug')`
  - `$kirby->routes()`
  - `Kirby::plugin('vendor/name', [...])`

## Example

```php
<?php

$kirby = kirby();
$templatesRoot = $kirby->root('templates');
```

## MCP: Inspect/verify

- Start with `kirby_init` and/or read `kirby://info` to confirm Kirby/PHP versions and runtime status.
- Resolve runtime paths with `kirby_roots` (or `kirby://roots`) before assuming `site/`, `content/`, etc.
- List registered routes with `kirby_routes_index` (requires `kirby_runtime_install`) when debugging routing/custom endpoints.
- If you need to inspect runtime state quickly, use `kirby_eval` for small, read-only checks (e.g. `return kirby()->root('templates');`).
- For official docs on any `$kirby->method()`, use `kirby_online` with the exact method name.

## Related terms

- kirby://glossary/roots
- kirby://glossary/option
- kirby://glossary/site
- kirby://glossary/page
- kirby://glossary/route
- kirby://glossary/plugin

## Links

- https://getkirby.com/docs/reference/objects/cms/app
- https://getkirby.com/docs/reference/templates/helpers/kirby
- https://getkirby.com/docs/reference/templates/helpers
