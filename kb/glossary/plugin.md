# Plugin (aliases: `Kirby::plugin()`, `site/plugins/*`, “Kirby plugin”)

## Meaning

A Kirby plugin is a package of code that extends Kirby. Kirby loads plugins from the plugins root (often `/site/plugins/<plugin>/index.php`).

Plugins are registered with `Kirby::plugin('vendor/name', [...])` and can contribute many extension points: routes, hooks, page models, custom Panel fields, snippets/templates, CLI commands, etc.

## In prompts (what it usually implies)

- “Create a plugin” means: create a folder in the plugins root and register extensions in `index.php`.
- “This is from a plugin” means: runtime truth may differ from filesystem (plugins can register templates/snippets/blueprints programmatically).
- “Add a custom field/section” means: Panel plugin development (PHP + Vue).

## Variants / aliases

- `Kirby::plugin('vendor/name', [...])`
- Plugins root: `…/plugins/`
- Extensions catalog: `kirby://extensions` and `kirby://extension/{name}`
- Common extensions:
  - `routes` (see kirby://glossary/route)
  - `hooks` (see kirby://glossary/hook)
  - `pageModels` (see kirby://glossary/page-model)

## Example

```php
Kirby::plugin('acme/example', [
    'routes' => [
        [
            'pattern' => 'health',
            'action'  => fn () => 'ok',
        ],
    ],
]);
```

## MCP: Inspect/verify

- Use `kirby_plugins_index` to list installed plugins and their paths.
- To locate plugin-defined routes at runtime, use `kirby_routes_index` and look for `source.pluginId` + `source.relativePath` (requires `kirby_runtime_install`).
- For official extension docs, use:
  - `kirby://extensions` (overview)
  - `kirby://extension/{name}` (details, e.g. `routes`, `page-models`, `fields`)
- If you need plugin-provided CLI commands, check `kirby://commands` (plugins can register commands).

## Related terms

- kirby://glossary/hook
- kirby://glossary/route
- kirby://glossary/page-model
- kirby://glossary/blueprint
- kirby://glossary/component

## Links

- https://getkirby.com/docs/guide/plugins/custom-plugins
- https://getkirby.com/docs/guide/plugins/plugin-setup-basic
- https://getkirby.com/docs/reference/plugins/extensions
