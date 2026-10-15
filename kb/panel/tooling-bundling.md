# Bundling or no build (Panel plugins)

## When to use

- No bundler: simple plugins with a small `index.js` + `index.css` and no modern build features.
- Bundler: single-file components, TypeScript, PostCSS, or when you want a dev server and HMR.

## Minimal setup

Without a build step, keep `index.js` and optional `index.css` in the plugin root; Kirby loads them conventionally for the registered plugin:

```php
Kirby::plugin('vendor/audio-block', []);
```

With a build step, use `kirbyup` and emit the production assets into the plugin root.

```json
{
  "scripts": {
    "dev": "kirbyup dev src/index.js",
    "build": "kirbyup src/index.js"
  }
}
```

## Dev workflow

- Kirby 6: kirbyup 4 / Node.js 24+, `kirbyup dev src/index.js` for HMR (the `next` package line at beta.1).
- Kirby 5 legacy `serve` and Vue 2 bundles must be replaced with this Vue 3 build.
- No bundler: edit `index.js`/`index.css` and reload the Panel.

## Build output

- `kirbyup` produces `index.js` and `index.css` in the configured output directory.
- Dev server builds write `index.dev.js`; ignore it and exclude it from releases.
- Ensure production files are in the plugin root, not only in `src/`.

## Compatibility notes

- Use `kirbyup` as the default bundler for Panel plugins.
- Bundle Kirby-6-compatible kirbyuse into your plugin and leave Vue external. Tagged beta.1 only maps `vue`; see `kirby://kb/panel/tooling-kirbyuse` before adding custom imports.
- If you only ship plain JS, keep your component templates small and avoid heavy logic.
- Converting SFCs to plain JS: move `<script>` logic into the component object, `<template>` into a `template` string, and `<style>` into `index.css`.

## MCP: Inspect/verify

- Resolve paths: `kirby://roots`
- Verify plugin load: `kirby_plugins_index`
- Check dev mode: `kirby://config/panel.dev`
- Tooling docs: `kirby://kb/panel/tooling-kirbyup`, `kirby://kb/panel/tooling-kirbyuse`
- Scenario: `kirby://kb/scenarios/panel-bundling-decisions`

## Links

- https://getkirby.com/docs/cookbook/plugins/to-bundle-or-not-to-bundle
- https://github.com/johannschopplich/kirbyup
- https://github.com/johannschopplich/kirbyuse

## Kirby 6 checks

No-build components still need Vue 3-compatible code. Import from `vue` rather than a global `Vue`. Preserve Kirby's `value`/`input` interface and declare emitted events. Verify production loading with no development entry present. Source: https://getkirby.com/releases/6/migration/plugin-developers/vue-3.
