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
   - `Kirby::plugin(..., ['fields' => ['doi' => []]])`
2. Register the Vue field component in `src/index.js`:
   - `panel.plugin(..., { fields: { doi: DoiField } })`
3. Implement the Vue field component and align with Kirby UI components.
4. Use the field in a blueprint and test in the Panel.

## Examples (from the cookbook recipe; abridged)

### PHP field registration

```php
Kirby::plugin('pluginAuthor/doi', [
  'fields' => [
    'doi' => [],
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
