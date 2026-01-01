# kirbyuse (Panel composables and typings)

## When to use

- Add typed access to `window.panel` in Panel plugins.
- Use Composition API helpers without relying on global `Vue` imports.
- Reuse Panel-specific composables for blocks, content, dialogs, and sections.

## Minimal setup

Install and import in your Panel plugin build:

```js
import { ref, usePanel, useContent } from 'kirbyuse';
```

Kirby 6 requires an import map entry (added once during Panel bootstrap):

```php
<?php

use Kirby\Cms\App as Kirby;

Kirby::plugin('vendor/plugin', [
  'panel' => [
    'extend' => function (Kirby $kirby, array &$assets): void {
      $assets['import-maps'] = array_merge(
        $assets['import-maps'] ?? [],
        [
          'kirbyuse' => url('media/plugins/vendor/plugin/kirbyuse/index.js'),
        ]
      );
    },
  ],
]);
```

The import-map URL should point to a bundled copy of `kirbyuse/dist/index.mjs` (or your own build artifact).

## Core features

- Type augmentation for `window.panel` to improve IntelliSense.
- Composables: `usePanel`, `useApi`, `useApp`, `useBlock`, `useContent`, `useDialog`, `useI18n`, `useSection`, `useHelpers`, `useLibrary`.
- Re-exports of Vue Composition API functions (`ref`, `computed`, `watch`, etc.).

## Dev workflow

- Use a bundler (kirbyup or other) to include `kirbyuse` in your plugin assets.
- Keep `kirbyuse` version aligned with the Kirby major version you target.
- For Kirby 6, ensure the import map entry exists before any Panel JS imports `kirbyuse`.

## Compatibility notes

- `useContent` uses `window.panel.content` and expects Kirby 5.0.0-rc.1+ in the Kirby 5 line.
- `useBlock` mirrors the default block component methods (`field`, `open`, `update`) for Composition API use.
- `useStore` and `isKirby4`/`isKirby5` exist only in 1.x for Kirby 4/5 compatibility.

## MCP: Inspect/verify

- `kirby_roots` to locate the plugin root and assets path.
- `kirby_plugins_index` to confirm the plugin loads at runtime.
- `kirby_blueprints_index` to verify where custom fields/sections are used.

## Links

- https://github.com/johannschopplich/kirbyuse
- https://github.com/johannschopplich/kirbyuse/tree/feat/kirby-6

## Version notes (K5/K6)

- K5: `kirbyuse` 1.x targets Kirby 4/5 and uses Vue 2.7 via the global `window.Vue`. It includes `isKirby4`/`isKirby5` and the deprecated `useStore` (Vuex removed in Kirby 5).
- K6: `kirbyuse` 2.x (feat/kirby-6, `v2.0.0-beta.1`) targets Kirby 6+ and Vue 3. It uses native ESM imports from `vue` and requires an import map entry for `kirbyuse`.
- K5 -> K6: remove `useStore`/compatibility checks, switch to `kirbyuse` 2.x, register the import map, and ensure your bundler targets Vue 3.
