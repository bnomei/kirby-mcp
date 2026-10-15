# Barcode drawer field: Kirby 5 legacy sketch and Kirby 6 port

## Goal

Build a custom field that opens a drawer for editing, validates input, and renders a live preview (for example a barcode).

Kirby 6: use a PHP field class (e.g. a `TextField` subclass) and server-side validation. For server-backed drawers implement `drawers()` and open a path below `endpoints.field`; see `kirby://kb/panel/reference-fields` and `kirby://kb/panel/reference-dialogs`.

## Inputs to ask for

- Input format and validation rules
- Preview rendering method (SVG, canvas)
- Drawer form fields and helper text
- Display options (height, font, colors, margins)

## MCP tools/resources to use

- kirby://roots
- kirby_plugins_index
- kirby_blueprints_index
- kirby_blueprint_read
- kirby://extension/fields

## Files to touch

- site/plugins/<plugin>/index.php
- site/plugins/<plugin>/index.js or site/plugins/<plugin>/src/index.js
- site/plugins/<plugin>/src/components/<Field>.vue
- site/plugins/<plugin>/index.css

## Implementation steps

1. Define field `props` in PHP for validation/display options; set defaults for missing blueprint values.
2. Extend `k-field` and render a preview area in the field body.
3. Use `usePanel().drawer.open()` with a `k-form-drawer` for focused editing.
4. Validate input on submit and emit `input` and `change` events.
5. Render a preview using a client-side library, updating on value changes.
6. If the plugin ships prebuilt JS, keep the drawer logic and preview rendering isolated.

## Examples

- Drawer includes a single text input plus an info block.
- Preview uses SVG and updates after each successful submit.
- Invalid input triggers a negative Panel notification.

## Kirby 5 legacy frontend sketch (migration reference)

For Kirby 6, use Vue 3 imports, declare `emits: ['input', 'change']`, replace the old `slot="options"` syntax with `<template #options>`, and render only after mount/`nextTick`. Clean up preview-library state on unmount. Verify the drawer API in the installed tag rather than assuming this legacy sketch is compatible.

```js
// site/plugins/example-barcode-field/src/components/BarcodeField.vue
import { computed, ref, usePanel, watch } from 'kirbyuse';

const BarcodeField = {
  extends: 'k-field',
  props: {
    value: String,
  },
  setup(props, { emit }) {
    const panel = usePanel();
    const preview = ref(null);
    const hasValue = computed(() => props.value && props.value.trim() !== '');

    const isValid = (value) => value.length >= 4;

    const renderPreview = () => {
      if (!hasValue.value) return;
      // Call a barcode library here and render into preview.value
    };

    const handleSubmit = (values) => {
      const next = values.code || '';
      if (!isValid(next)) {
        panel.notification.error('Invalid code');
        return;
      }
      emit('input', next);
      emit('change', next);
      panel.drawer.close();
    };

    const openDrawer = () => {
      panel.drawer.open({
        component: 'k-form-drawer',
        props: {
          title: 'Edit code',
          fields: {
            code: {
              label: 'Code',
              type: 'text',
              minlength: 4,
            },
          },
          value: { code: props.value },
        },
        on: {
          submit: handleSubmit,
        },
      });
    };

    watch(
      () => props.value,
      () => renderPreview(),
    );

    renderPreview();

    return { preview, hasValue, openDrawer, renderPreview };
  },
  template: `
    <k-field v-bind="$props" class="k-barcode-field">
      <k-button slot="options" icon="edit" size="xs" @click="openDrawer">Edit</k-button>
      <div class="k-barcode-preview">
        <svg ref="preview"></svg>
      </div>
    </k-field>
  `,
};

panel.plugin('example/barcode-field', {
  fields: {
    barcode: BarcodeField,
  },
});
```

## Verification

- Drawer opens and closes reliably from the field options.
- Validation rejects invalid input and preserves the old value.
- Preview updates when the value changes.

## Gotchas

- Don't mutate `props.value` directly; always `$emit('input', next)` to update the stored value.
- If the preview library touches the DOM, re-render after refs exist (`onMounted`/`nextTick`).

## Links

- https://getkirby.com/docs/reference/plugins/extensions/fields
- https://getkirby.com/docs/reference/plugins/ui

## Migration acceptance

Test valid/invalid submit, cancel without changing content, preview updates after mount, and save/reload. Rebuild with kirbyup 4; never treat client validation as the storage contract.
