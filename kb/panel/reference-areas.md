# Panel areas (type: areas)

## What it is

Panel areas are top-level Panel apps with their own routes, views, menu item, icon, and breadcrumb. They can be simple single-view pages or full mini apps with custom dialogs, dropdowns, and searches.

## PHP registration

Register areas in a plugin and return a configuration array from a callback:

```php
Kirby::plugin('vendor/todos', [
  'areas' => [
    'todos' => function ($kirby) {
      return [
        'label' => 'Todos',
        'icon' => 'check',
        'menu' => true,
        'link' => 'todos',
        'views' => [
          [
            'pattern' => 'todos',
            'action' => function () {
              return [
                'component' => 'k-todos-view',
                'title' => 'Todos',
                'props' => [
                  'items' => [],
                ],
                'search' => 'pages',
              ];
            },
          ],
        ],
      ];
    },
  ],
]);
```

## Vue registration

Register the view component name returned by the PHP action:

```js
import TodosView from './components/TodosView.vue';

panel.plugin('vendor/todos', {
  components: {
    'k-todos-view': TodosView,
  },
});
```

## Data flow (props/events/load)

- View actions return a plain array that is serialized to JSON and passed to the Panel.
- The `component` key controls which Vue component renders the view.
- `props` are injected into the view component as props.
- Route patterns are relative to `panel/` (do not include the prefix).
- Use dialogs, dropdowns, and searches inside the same area to round out the UI.

## Common UI components

- `k-inside`, `k-view`, `k-header`
- `k-button`, `k-table`, `k-card`
- `k-options-dropdown`

Use the Panel Lab to inspect component props and examples.

## Gotchas

- `menu` can be a boolean or a callback; hidden areas can be added via `panel.menu` config.
- `link` can be an absolute URL; use `target` to open in a new tab.
- When extending core areas, only override the view parts you need to change.

## MCP: Inspect/verify

- Resolve paths: `kirby://roots`
- Verify plugin load: `kirby_plugins_index`
- Check custom menu config: `kirby://config/panel.menu`
- Reference doc: `kirby://extension/panel-areas`

## Links

- https://getkirby.com/docs/reference/plugins/extensions/panel-areas
- https://getkirby.com/docs/cookbook/panel/first-panel-area
- https://getkirby.com/docs/cookbook/panel/advanced-panel-area
- https://getkirby.com/docs/reference/plugins/ui
- https://lab.getkirby.com/public/lab
- https://github.com/getkirby/kirby/tree/main/panel

## Version notes (K5/K6)

- K5: Vue 2 Panel runtime; use `kirbyup` 3.x with HMR.
- K6: Vue 3 Panel runtime; use `kirbyup` 4.x and watch-mode builds.
- K5 -> K6: update Vue components to Vue 3 syntax and recheck any core view overrides.
