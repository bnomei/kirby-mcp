# Scenario: Custom Panel field

## Goal

Create a custom Panel field type with a Vue component and register it in PHP.

## Inputs to ask for

- Plugin id (`vendor/name`)
- Field type name (slug) and label
- Field behavior (input type, validation, UI components)
- Blueprint(s) where the field is used

## Internal tools/resources to use

- Resolve project paths: `kirby://roots`
- Confirm plugin is loaded: `kirby_plugins_index`
- Inspect blueprints: `kirby_blueprints_index` + `kirby_blueprint_read`

## Implementation steps

1. Create a plugin folder and register the field in `index.php`.
2. Create `src/index.js` and register the field component with `panel.plugin`.
3. Implement a Vue component that binds `value` and emits `input`.
4. Reference the field type in a blueprint.
5. Build or serve assets with `kirbyup`.

## Examples

### Plugin registration (PHP)

`site/plugins/doifield/index.php`

```php
<?php

Kirby::plugin('vendor/doifield', [
  'fields' => [
    'doi' => [],
  ],
]);
```

### Vue entry point

`site/plugins/doifield/src/index.js`

```js
import DoiField from './components/fields/DoiField.vue';

panel.plugin('vendor/doifield', {
  fields: {
    doi: DoiField,
  },
});
```

### Field component

`site/plugins/doifield/src/components/fields/DoiField.vue`

```vue
<template>
  <k-input theme="field" type="text" :value="value" @input="onInput" />
</template>

<script>
export default {
  props: {
    value: String,
  },
  methods: {
    onInput(value) {
      this.$emit('input', value);
    },
  },
};
</script>
```

### Blueprint usage

`site/blueprints/pages/sandbox.yml`

```yaml
fields:
  doi:
    type: doi
    label: DOI
```

## Verification

- Build or serve assets with `kirbyup`.
- Open the Panel and confirm the field renders.
- Update the field and verify that Kirby registers content changes.

## Glossary quick refs

- kirby://glossary/panel
- kirby://glossary/blueprint
- kirby://glossary/field
- kirby://glossary/kirbyuse

## Links

- Cookbook: https://getkirby.com/docs/cookbook/panel/first-panel-field
- Panel fields: https://getkirby.com/docs/reference/plugins/extensions/fields
- Panel tooling: kirby://kb/panel/tooling-kirbyup
- Panel composables: kirby://kb/panel/tooling-kirbyuse

## Version notes (K5/K6)

- K5: Vue 2 Panel runtime; `kirbyup` 3.x targets Vue 2.7.
- K6: Vue 3 Panel runtime; `kirbyup` 4.x targets Vue 3, and `kirbyuse` 2.x requires an import map entry.
- K5 -> K6: upgrade to `kirbyuse` 2.x (if used), update Vue component syntax if needed, and switch bundler versions.
