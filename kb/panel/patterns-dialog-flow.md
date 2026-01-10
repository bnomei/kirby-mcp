# Dialog flow (pattern)

## Goal

Create a modal dialog with `load` and `submit` callbacks.

## When to use

- Create/edit forms triggered from dropdowns or buttons.
- Destructive actions that need confirmation.

## Pattern

1. Define a dialog pattern in the area with `load` and `submit` callbacks.
   - `load` maps to GET `/panel/dialogs/{pattern}`, `submit` maps to POST.
2. Use `k-form-dialog` and return `fields` + `value` from `load`.
3. Return `true` on success or `['event' => 'name', 'data' => [...]]` to emit an event from `submit`.
4. Throw exceptions from `submit` to show an error dialog.
5. Use `Panel::go()` in `submit` when you need to redirect after success.
6. Trigger the dialog via a dropdown `dialog` option or the Panel dialog helper.

## Example

```php
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
```

## MCP: Inspect/verify

- Dialog reference: `kirby://extension/panel-dialogs`
- KB reference: `kirby://kb/panel/reference-dialogs`
- Verify plugin load: `kirby_plugins_index`

## Links

- https://getkirby.com/docs/reference/plugins/extensions/panel-dialogs
- https://lab.getkirby.com/public/lab

## Version notes (K5/K6)

- K5: Vue 2 dialog components.
- K6: Vue 3 dialog components.
- K5 -> K6: update any custom dialog UI components to Vue 3 syntax.
