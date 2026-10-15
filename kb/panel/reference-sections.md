# Panel sections: existing configuration and custom-field ports

Existing built-in sections remain supported through automatic conversion. For new blueprints, prefer their field equivalents: `pages` → `pagelist`, `files` → `filelist`, and `info`/`stats` → same-named fields; `fields` wrappers are unwrapped. Picker fields are different: use `pagepicker`/`filepicker`/`userpicker` for stored selections. Never send content updates for display fields.

Custom sections require a port, not just a frontend rebuild: move both PHP and JavaScript registrations from `sections` to `fields`, extend `Kirby\Form\Field\DisplayField`, and change blueprint placements to `fields`.

## A computed display field

```php
<?php

use Kirby\Form\Field\DisplayField;

class ModifiedField extends DisplayField
{
    public function props(): array
    {
        return [
            ...parent::props(),
            'text' => 'Last update: ' . $this->model()->modified('Y-m-d H:i'),
        ];
    }
}

Kirby::plugin('example/modified', [
    'fields' => ['modified' => ModifiedField::class],
]);
```

```js
panel.plugin('example/modified', {
  fields: {
    modified: {
      inheritAttrs: false,
      props: { label: String, text: String },
      template: `<k-field :label="label"><p>{{ text }}</p></k-field>`,
    },
  },
});
```

```yaml
fields:
  modified:
    type: modified
    label: Last update
```

## Where section code goes

| Kirby 5 legacy                   | Kirby 6 replacement                              |
| -------------------------------- | ------------------------------------------------ |
| `props` closures                 | Typed `#[Prop]` properties and getters           |
| `computed`                       | Getters or lazy API methods                      |
| `methods`                        | PHP methods                                      |
| `toArray`                        | `props()` merged with parent props               |
| `this.load()` / section endpoint | Cheap data as props; expensive data from `api()` |
| API root response                | Field route with `pattern: ''`                   |
| `sections` JS registry           | `fields` JS registry                             |
| `headline`                       | `label`                                          |

For asynchronous data, expose a GET `api()` route and fetch `this.endpoints.field` in Vue. See `kirby://kb/panel/patterns-section-load`. Don't compute a site-wide index in a constructor; field creation also happens on saves. Implement `errors()` if the display needs to report model errors, and enforce authorization on every action endpoint.

## Verify the port

Use `kirby_plugins_index`, inspect the raw blueprint, open the Panel and test loading/error/empty states. Confirm no field content key is written after saving. Check names against all other fields case-insensitively. Existing paths for this article and the section recipes are retained for migration discovery, not as a recommendation to build new sections.

## Links

- https://getkirby.com/releases/6/migration/plugin-developers/sections
- https://getkirby.com/releases/6/migration/plugin-developers/fields
- https://getkirby.com/releases/6/blueprints
