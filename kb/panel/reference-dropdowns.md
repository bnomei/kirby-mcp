# Panel dropdowns (type: dropdowns)

## What it is

Dropdowns are action menus for pages, files, users, or custom items. Options are loaded on demand and can trigger dialogs or events.

## PHP registration

```php
Kirby::plugin('vendor/todos', [
  'areas' => [
    'todos' => function () {
      return [
        'dropdowns' => [
          'todos/(:any)' => function (string $id) {
            return [
              [
                'text' => 'Edit',
                'icon' => 'edit',
                'dialog' => 'todos/' . $id . '/edit',
              ],
              [
                'text' => 'Delete',
                'icon' => 'trash',
                'dialog' => 'todos/' . $id . '/delete',
              ],
            ];
          },
        ],
      ];
    },
  ],
]);
```

## Vue registration

Use Panel dropdown components and load options from the backend:

```html
<k-dropdown>
  <k-button @click="$refs.options.toggle()">Options</k-button>
  <k-dropdown-content ref="options" :options="$dropdown('todos/' + id)" />
</k-dropdown>
```

## Data flow (props/events/load)

- Dropdown callbacks return a list of option objects.
- `dialog` values refer to dialog patterns without the `/panel/dialogs` prefix.
- `click` can trigger a global event for custom handling.
- Option settings include `text`, `icon`, `dialog`, `click`, `link`, `target`, and `disabled`.
- `dialog` can be a string pattern or a full dialog config array.
- `click` can be a string or `['global' => 'myEvent', 'payload' => [...]]` and listened to with `this.$events.on('myEvent', ...)`.

## Common UI components

- `k-dropdown`, `k-dropdown-content`
- `k-button`, `k-options-dropdown`

## Gotchas

- Patterns are matched against the dropdown path; keep them unique and predictable.
- Use `(:any)` (or other route tokens) when you need dynamic IDs in the pattern.
- Reuse core dropdowns with `$page->panel()->dropdown()` or `$kirby->core()->area('site')['dropdowns']['page']['options']`.
- Use `click` events when you need to handle a custom action in Vue.
- Core dropdown names: `site/page`, `site/page.file`, `site/site.file`, `users/user`, `users/user.file`.

## MCP: Inspect/verify

- Verify plugin load: `kirby_plugins_index`
- Extension reference: `kirby://extension/panel-dropdowns`
- Panel source: https://github.com/getkirby/kirby/tree/main/config/areas

## Links

- https://getkirby.com/docs/reference/plugins/extensions/panel-dropdowns
- https://getkirby.com/docs/reference/plugins/extensions/panel-areas
- https://getkirby.com/docs/reference/plugins/ui
- https://lab.getkirby.com/public/lab

## Version notes (K5/K6)

- K5: Vue 2 Panel runtime; dropdown helpers are Vue 2 based.
- K6: Vue 3 Panel runtime; dropdown helpers are Vue 3 based.
- K5 -> K6: update any custom dropdown components to Vue 3 syntax.
