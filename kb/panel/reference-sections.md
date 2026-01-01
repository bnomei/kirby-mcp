# Panel sections (type: sections)

## What it is

Sections are layout blocks in blueprints for listing, navigation, or informational UI. Use sections for display and actions, fields for storing content.

## PHP registration

```php
Kirby::plugin('vendor/modified', [
  'sections' => [
    'modified' => [
      'props' => [
        'label' => function ($label = null) {
          return $label;
        },
      ],
      'computed' => [
        'text' => function () {
          return 'Last update: ' . $this->model()->modified('Y-m-d H:i');
        },
      ],
      'api' => function () {
        return [
          [
            'pattern' => '/say-hello',
            'action' => function () {
              return ['message' => 'Hello, world'];
            },
          ],
        ];
      },
    ],
  ],
]);
```

## Vue registration

```js
import { ref, useSection } from 'kirbyuse';

panel.plugin('vendor/modified', {
  sections: {
    modified: {
      setup() {
        const label = ref(null);
        const text = ref(null);
        const { load } = useSection();

        load().then((response) => {
          label.value = response.label;
          text.value = response.text;
        });

        return { label, text };
      },
      template: `
        <section class="k-modified-section">
          <k-label>{{ label }}</k-label>
          <k-text>{{ text }}</k-text>
        </section>
      `,
    },
  },
});
```

## Data flow (props/events/load)

- Blueprint options map to PHP `props` setters and become JSON in the Panel response.
- Sections load asynchronously. Use `useSection().load()` to fetch the data from the backend.
- Populate reactive refs with the response from `load()`.
- Custom API endpoints are namespaced under `/pages/<id>/sections/<section>/...` and accessed via `useApi()`.

## Common UI components

- `k-section`, `k-collection`, `k-item`
- `k-label`, `k-text`, `k-button`

## Gotchas

- Sections do not receive props directly; define `data()` and set values after `load()`.
- Use sections for UI that does not write content; use fields when you need stored values.
- Keep `data()` keys in sync with PHP props and computed values.

## MCP: Inspect/verify

- Find usage: `kirby_blueprints_index` + `kirby_blueprint_read`
- Verify plugin load: `kirby_plugins_index`
- Core section reference: `kirby://section/{type}`
- Extension reference: `kirby://extension/sections`

## Links

- https://getkirby.com/docs/reference/plugins/extensions/sections
- https://getkirby.com/docs/reference/panel/sections
- https://getkirby.com/docs/cookbook/panel/first-panel-section
- https://getkirby.com/docs/reference/plugins/ui
- https://lab.getkirby.com/public/lab

## Version notes (K5/K6)

- K5: Vue 2 Panel runtime; `kirbyup` 3.x and `kirbyuse` 1.x.
- K6: Vue 3 Panel runtime; `kirbyup` 4.x and `kirbyuse` 2.x with import maps.
- K5 -> K6: update component syntax and re-test async loading.
