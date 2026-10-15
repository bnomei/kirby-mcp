# Scenario: Panel plugin “without bundler” (no SFC build step)

## Goal

Ship a Panel plugin without a JS bundling step by:

- registering components via `panel.plugin(...)`
- embedding templates as strings (instead of `.vue` single-file components)

This can reduce build tooling overhead for small plugins.

## Inputs to ask for

- Which Panel component you’re building (value/display field, block or area)
- Whether the UI is simple enough to maintain as template strings
- Whether styles can be shipped as plain CSS
- Whether the plugin will grow beyond a single component (consider bundler if so)

## Internal tools/resources to use

- Inventory plugins: `kirby_plugins_index`
- Panel docs reference:
  - `kirby://fields`, `kirby://extensions`

## Implementation steps

1. Move `<script>` logic into your plugin `index.js` as a normal Vue component object.
2. Move `<template>` into a `template: \`...\`` string.
3. Move styles into a dedicated CSS file.
4. Place `index.js` and `index.css` in the plugin root; Kirby loads these conventionally.
5. If the UI grows beyond a single component, switch to a bundler workflow (kirbyup).

## Examples

```php
Kirby::plugin('acme/myblock', []);
```

```js
panel.plugin('acme/myblock', {
  blocks: {
    audio: {
      template: `<div class="k-block-type-audio-wrapper">...</div>`,
      props: {
        content: Object,
      },
    },
  },
});
```

## Verification

Use Vue 3 for Kirby 6. Import Vue helpers from `vue` in ESM; do not use `window.Vue`. Kirby 5 legacy sections need PHP display-field classes, and array field definitions need class ports. If using kirbyuse, bundle it instead: beta.1 has no plugin import-map hook. See `kirby://kb/panel/compat-k5-k6-migration`.

- Open the Panel and confirm the plugin UI renders without a bundler build.
- Confirm the plugin still works when installed from a clean zip/release.

## Glossary quick refs

- kirby://glossary/panel
- kirby://glossary/kirbyuse
- kirby://glossary/plugin
- kirby://glossary/template
- kirby://glossary/component

## Links

- Cookbook: To bundle or not to bundle: https://getkirby.com/docs/cookbook/plugins/to-bundle-or-not-to-bundle
- Guide: Panel plugin setup: https://getkirby.com/docs/guide/plugins/plugin-setup-panel
