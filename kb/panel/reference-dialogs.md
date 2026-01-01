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
- Return `true` to close the dialog, return an array to emit an event, or throw an exception to show an error.
- Use `Panel::go()` to redirect after submit if needed.

## Common UI components

- `k-form-dialog`
- `k-text-dialog`
- `k-remove-dialog`
- `k-error-dialog`

## Gotchas

- Returning `null` or `false` from `submit` results in a 404.
- Dialog patterns are scoped to areas; keep them unique.
- Use dropdown `dialog` options or the Panel dialog helper to open dialogs.

## MCP: Inspect/verify

- Verify plugin load: `kirby_plugins_index`
- Extension reference: `kirby://extension/panel-dialogs`
- Panel source: https://github.com/getkirby/kirby/tree/main/config/areas

## Links

- https://getkirby.com/docs/reference/plugins/extensions/panel-dialogs
- https://getkirby.com/docs/reference/plugins/extensions/panel-areas
- https://getkirby.com/docs/reference/plugins/ui
- https://lab.getkirby.com/public/lab

## Version notes (K5/K6)

- K5: Vue 2 Panel runtime; dialog components are Vue 2 based.
- K6: Vue 3 Panel runtime; dialog components are Vue 3 based.
- K5 -> K6: update any custom dialog components to Vue 3 syntax.
