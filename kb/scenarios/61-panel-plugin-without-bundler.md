# Scenario: Panel plugin “without bundler” (no SFC build step)

## Goal

Ship a Panel plugin without a JS bundling step by:

- registering components via `panel.plugin(...)`
- embedding templates as strings (instead of `.vue` single-file components)

This can reduce build tooling overhead for small plugins.

## Inputs to ask for

- Which Panel component you’re building (field/section/block/area)
- Whether the UI is simple enough to maintain as template strings
- Whether styles can be shipped as plain CSS

## Internal tools/resources to use

- Inventory plugins: `kirby_plugins_index`
- Panel docs reference:
  - `kirby://fields`, `kirby://sections`, `kirby://extensions`

## Implementation steps

1. Move `<script>` logic into your plugin `index.js` as a normal Vue component object.
2. Move `<template>` into a `template: \`...\`` string.
3. Move styles into a dedicated CSS file and register it for the Panel.

## Examples

```js
panel.plugin('acme/myblock', {
  blocks: {
    audio: {
      template: `<div class="k-block-type-audio-wrapper">...</div>`,
      props: {
        value: Object,
      },
    },
  },
});
```

## Verification

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
