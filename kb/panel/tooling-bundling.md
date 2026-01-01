# Bundling or no build (Panel plugins)

## When to use

- No bundler: simple plugins with a small `index.js` + `index.css` and no modern build features.
- Bundler: single-file components, TypeScript, PostCSS, or when you want a dev server and HMR.

## Minimal setup

Without a build step, keep assets in the plugin root and register them:

```php
Kirby::plugin('vendor/audio-block', [
  'panel' => [
    'js' => 'index.js',
    'css' => 'index.css',
  ],
]);
```

With a build step, use `kirbyup` and point the Panel assets to the build output.

```json
{
  "scripts": {
    "dev": "kirbyup serve src/index.js",
    "build": "kirbyup src/index.js"
  }
}
```

## Dev workflow

- K5: `kirbyup serve src/index.js` for HMR.
- K6: `kirbyup src/index.js --watch` for watch-mode rebuilds.
- No bundler: edit `index.js`/`index.css` and reload the Panel.

## Build output

- `kirbyup` produces `index.js` and `index.css` in the configured output directory.
- Dev server builds write `index.dev.mjs` to point the Panel at the dev server.
- Ensure `panel` asset paths match the build output location.

## Compatibility notes

- Use `kirbyup` as the default bundler for Panel plugins.
- `kirbyuse` 2.x (K6) requires an import map entry before any imports.
- If you only ship plain JS, keep your component templates small and avoid heavy logic.

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

## Version notes (K5/K6)

- K5: `kirbyup` 3.x supports HMR and Vue 2 Panel runtime.
- K6: `kirbyup` 4.x targets Vue 3; use watch mode until HMR is available.
- K5 -> K6: update build scripts and Panel asset paths if outputs changed.
