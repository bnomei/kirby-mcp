# Panel dialogs (type: dialogs)

## What it is

Dialogs are modal flows for create/update/delete actions. They are defined inside Panel areas and can be extended or overridden.

## PHP registration

```php
Kirby::plugin('vendor/todos', [
  'areas' => [
    'todos' => function () {
      return [
        'dialogs' => [
          'todos/create' => [
            'load' => function () {
              return [
                'component' => 'k-form-dialog',
                'props' => [
                  'fields' => [
                    'title' => [
                      'label' => 'Title',
                      'type' => 'text',
                    ],
                  ],
                  'value' => [
                    'title' => '',
                  ],
                ],
              ];
            },
            'submit' => function () {
              return true;
            },
          ],
        ],
      ];
    },
  ],
]);
```

## Vue registration

Most dialogs use built-in components (`k-form-dialog`, `k-text-dialog`, etc.). If you return a custom `component`, register it with `panel.plugin` and return the component name in `load`.

## Data flow (props/events/load)

- `load` is called via GET at `/panel/dialogs/{pattern}` and returns the dialog component and props.
- `submit` is called via POST at the same path and returns a result.
- Return `true` to close the dialog, return `['event' => 'name', 'data' => [...]]` to emit an event, or throw an exception to show an error.
- Use `Panel::go()` to redirect after submit if needed.
- The dialog key defines the `{pattern}`; omit the `panel/` prefix.

Kirby 6 retains `load`/`submit` closures and supports a controller class under `action`. Extend `Kirby\Panel\Controller\DialogController` (or `DrawerController`), use `factory()` to resolve route arguments and implement `load()`/`submit()`. Kirby 5 legacy `controller`/`::for()` examples must be ported to this contract. Drawers live under the area's `drawers` key. Return UI objects such as `Kirby\Panel\Ui\Dialog\FormDialog` or existing component/props arrays.

Plain text props are escaped in Kirby 6. For markup, use trusted `HtmlString` values with escaped substitutions, not raw user HTML.

## Common UI components

- `k-form-dialog`
- `k-text-dialog`
- `k-remove-dialog`
- `k-error-dialog`

## Gotchas

- Returning `null` or `false` from `submit` results in a 404.
- Dialog patterns are scoped to areas; keep them unique.
- Core routes now contain controller classes, not callable array handlers. Reuse e.g. `Kirby\Panel\Controller\Dialog\PageChangeStatusDialogController::factory($id)->load()`; `load`/`submit` overrides take precedence over the core action.
- Use dropdown `dialog` options or the Panel dialog helper to open dialogs.

## MCP: Inspect/verify

- Verify plugin load: `kirby_plugins_index`
- Extension reference: `kirby://extension/panel-dialogs`
- Panel source: https://github.com/getkirby/kirby/tree/main/config/areas

## Links

- https://getkirby.com/docs/reference/plugins/extensions/panel-dialogs
- https://getkirby.com/docs/reference/plugins/extensions/panel-drawers
- https://getkirby.com/docs/reference/plugins/extensions/panel-areas
- https://getkirby.com/docs/reference/plugins/ui
- https://lab.getkirby.com/public/lab

## Kirby 6 migration

Rebuild legacy Vue 2 dialog components for Vue 3, declare emitted events and preserve Kirby inputs' `value`/`input` contract. See `kirby://kb/panel/compat-k5-k6-migration`.
