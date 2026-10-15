# Scenario: Create a custom Panel field (plugin + Vue field)

## Goal

Add a new field type for the Panel (blueprints) with custom UI and optional backend logic.

## Inputs to ask for

- New field type name (e.g. `doi`)
- Stored value shape (string/array/object) and validation requirements
- Whether backend logic is needed (API calls, computed defaults)
- Whether a build pipeline is available for Panel assets (kirbyup)

## Internal tools/resources to use

- Panel field reference: `kirby://fields` + `kirby://field/{type}`
- Inventory plugins: `kirby_plugins_index`
- Confirm roots: `kirby://roots`

## Implementation steps

1. Register the field type in PHP:
   - `Kirby::plugin(..., ['fields' => ['doi' => Kirby\Form\Field\TextField::class]])`
   - subclass a core field or `InputField` for custom storage; use typed `#[Prop]` options, getters and `api()` methods (see `kirby://kb/panel/reference-fields`)
2. Register the Vue field component in `src/index.js`:
   - `panel.plugin(..., { fields: { doi: DoiField } })`
3. Implement the Vue field component and align with Kirby UI components.
   - Kirby 6 uses Vue 3 but retains `value`/`input`: bind explicitly and declare `emits: ['input']`
   - add optional `index.css` if needed
4. Use the field in a blueprint and test in the Panel.

## Examples (Kirby 6)

### PHP field registration

```php
Kirby::plugin('pluginAuthor/doi', [
  'fields' => [
    'doi' => Kirby\Form\Field\TextField::class,
  ],
]);
```

### JS field registration

```js
import DoiField from './components/fields/DoiField.vue';

panel.plugin('pluginAuthor/doi', {
  fields: {
    doi: DoiField,
  },
});
```

## Verification

- Kirby 5 legacy array field registrations are removed; port the PHP class and rebuild with kirbyup 4, not just Vue syntax changes.
- Add the field to a blueprint and confirm it renders in the Panel.
- Save content and confirm stored value matches expectations.

## Glossary quick refs

- kirby://glossary/panel
- kirby://glossary/kirbyuse
- kirby://glossary/field
- kirby://glossary/plugin
- kirby://glossary/component

## Links

- Cookbook: First Panel field: https://getkirby.com/docs/cookbook/panel/first-panel-field
- Reference: Panel fields extension: https://getkirby.com/docs/reference/plugins/extensions/fields
- Guide: Panel plugin setup: https://getkirby.com/docs/guide/plugins/plugin-setup-panel
