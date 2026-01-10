# Panel fields (type: fields)

## What it is

Fields are editable inputs defined in blueprints. The Panel uses Vue and a REST API, so a custom field plugin bridges PHP (API/props) to a Vue component, plus optional CSS. Field plugins typically ship `index.php`, `index.js`, and optional `index.css`.

## PHP registration

```php
Kirby::plugin('vendor/hello', [
  'fields' => [
    'hello' => [
      'props' => [
        'message' => function ($message = null) {
          return $message;
        },
      ],
      'computed' => [
        'sentence' => function () {
          return $this->message;
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

To extend a core field in PHP:

```php
Kirby::plugin('vendor/hello', [
  'fields' => [
    'hello' => [
      'extends' => 'text',
    ],
  ],
]);
```

## Vue registration

```js
import HelloField from './components/HelloField.vue';

panel.plugin('vendor/hello', {
  fields: {
    hello: HelloField,
  },
});
```

To extend a core field:

```js
panel.plugin('vendor/hello', {
  fields: {
    hello: {
      extends: 'k-text-field',
    },
  },
});
```

## Data flow (props/events/load)

- Blueprint options map to PHP `props` setters and become JSON props in the Panel.
- `computed` values are appended to the API response and must be declared as props in Vue.
- Field value updates are emitted with `this.$emit("input", value)`.
- Custom field API endpoints are namespaced under `/pages/<id>/fields/<field>/...` and accessed via `useApi()`.

## Common UI components

- `k-field`, `k-input`, `k-label`
- `k-button`, `k-icon`, `k-text`

## Gotchas

- Declare all PHP props and computed values in the Vue component.
- Use default values in PHP props to avoid missing keys.
- Use `I18n::translate()` in prop setters if you expect translation arrays.
- `index.css` is auto-loaded; reuse Panel UI styles when possible.
- Backend `extends` inherits core field logic; frontend `extends` inherits the Vue component.
- Frontend `extends` uses the UI component name (for example `k-text-field`), not the field type (`text`).

## MCP: Inspect/verify

- Find usage: `kirby_blueprints_index` + `kirby_blueprint_read`
- Verify plugin load: `kirby_plugins_index`
- Core field reference: `kirby://field/{type}`
- Extension reference: `kirby://extension/fields`

## Links

- https://getkirby.com/docs/reference/plugins/extensions/fields
- https://getkirby.com/docs/reference/panel/fields
- https://getkirby.com/docs/cookbook/panel/first-panel-field
- https://getkirby.com/docs/reference/plugins/ui
- https://lab.getkirby.com/public/lab
- https://github.com/johannschopplich/kirbyuse

## Version notes (K5/K6)

- K5: Vue 2 Panel runtime; `kirbyup` 3.x and `kirbyuse` 1.x.
- K6: Vue 3 Panel runtime; `kirbyup` 4.x and `kirbyuse` 2.x with import maps.
- K5 -> K6: update component syntax and rebuild with the Vue 3 toolchain.
