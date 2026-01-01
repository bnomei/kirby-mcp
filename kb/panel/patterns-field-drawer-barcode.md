# DRAFT: Panel pattern - field with drawer editing and live preview

## Goal

Build a custom field that opens a drawer for editing, validates input, and renders a live preview (for example a barcode).

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
- kirby://extension/panel-fields

## Files to touch

- site/plugins/<plugin>/index.php
- site/plugins/<plugin>/index.js or site/plugins/<plugin>/src/index.js
- site/plugins/<plugin>/src/components/<Field>.vue
- site/plugins/<plugin>/index.css

## Implementation steps

1. Extend `k-field` and render a preview area in the field body.
2. Use `usePanel().drawer.open()` with a `k-form-drawer` for focused editing.
3. Validate input on submit and emit `input` and `change` events.
4. Render a preview using a client-side library, updating on value changes.
5. If the plugin ships prebuilt JS, keep the drawer logic and preview rendering isolated.

## Examples

- Drawer includes a single text input plus an info block.
- Preview uses SVG and updates after each successful submit.
- Invalid input triggers a negative Panel notification.

## Panel JS (K5)

```js
// site/plugins/acme-barcode-field/src/components/BarcodeField.vue
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

panel.plugin('acme/barcode-field', {
  fields: {
    barcode: BarcodeField,
  },
});
```

## Verification

- Drawer opens and closes reliably from the field options.
- Validation rejects invalid input and preserves the old value.
- Preview updates when the value changes.

## Links

- https://getkirby.com/docs/reference/plugins/extensions/panel-fields
- https://getkirby.com/docs/reference/plugins/ui

## Version notes (K5/K6)

- K5: Vue 2 field components and drawer API.
- K6: Vue 3 field components; confirm drawer APIs are unchanged.
- K5 -> K6: rebuild the field bundle and re-test preview rendering.
