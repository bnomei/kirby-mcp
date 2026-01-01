# Hook (aliases: `page.update:after`, “Kirby hook”, “event hook”)

## Meaning

Hooks let you run code when Kirby triggers specific events (e.g. page/file/user lifecycle events, route events, Panel route events).

Hooks are registered via the `hooks` plugin extension and are identified by names like `page.update:after`.

## In prompts (what it usually implies)

- “Run code after a page update” means: register a hook like `page.update:after`.
- “Intercept a route” can involve `route:before`/`route:after`.
- “Why does this happen on save?” often means: a hook is running.

## Variants / aliases

- Hook name forms:
  - name: `page.update:after`
  - slug: `page-update-after` (used in docs URLs)
- Registered via plugin extension: `hooks`

## Example

```php
Kirby::plugin('acme/hooks', [
    'hooks' => [
        'page.update:after' => function ($newPage, $oldPage) {
            // react to updates
        },
    ],
]);
```

## MCP: Inspect/verify

- Use `kirby://hooks` to browse hook names and jump to docs pages.
- Fetch official details for a hook via `kirby://hook/{name}` (accepts names like `page.update:after`).
- Inspect installed plugins (potential hook sources) with `kirby_plugins_index`.

## Related terms

- kirby://glossary/plugin
- kirby://glossary/route

## Links

- https://getkirby.com/docs/reference/plugins/hooks
