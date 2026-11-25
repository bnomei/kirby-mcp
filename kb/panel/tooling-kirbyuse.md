# kirbyuse for Kirby 6 Panel plugins

kirbyuse **2.0.0** is the stable release for the Vue 3 Panel. Install it with `npm install --save-dev kirbyuse@^2` and keep the lockfile. Its required Vue peer is `^3.5.0`; kirbyuse 1.x is for Kirby 4/5.

## Build setup

Bundle kirbyuse into the plugin JavaScript and leave `vue` external (kirbyup 4 does this by default). Kirby 6.0.0's `Assets::importMaps()` maps only `vue`; there is no plugin `importMaps` extension or `panel.extend` asset hook in this tag.

```js
import { ref } from 'vue';
import { usePanel, useContent } from 'kirbyuse';
```

The example is build input. A bare browser import from `kirbyuse` will not resolve in a stock Panel; a named import from `vue` uses the Panel's own runtime.

Kirby automatically loads a registered plugin's root `index.js` and optional `index.css`:

```php
Kirby::plugin('example/plugin', []);
```

For no-build plugins, prefer the Panel's built-in APIs and direct imports from `vue` instead of inventing a kirbyuse loader. See `kirby://kb/panel/tooling-bundling`.

## Migration checks

- Replace `useSection()` with field props or `endpoints.field` requests. Import `field` instead of `section` from `kirbyuse/props`. See `kirby://kb/panel/patterns-section-load`.
- Replace `useStore()` with `useContent()` for content state. Import composables from `kirbyuse`, not `kirbyuse/composables`, and Vue helpers from `vue`, not `kirbyuse`.
- Check the exact package's exports/types before using helpers; a 1.x recipe is not evidence that a 2.x helper still exists.
- Verify production asset loading, one Vue runtime, field input events and cleanup of watchers/listeners.

## Sources

- https://getkirby.com/releases/6/migration/plugin-developers/vue-3
- https://github.com/johannschopplich/kirbyuse/tree/v2.0.0
- https://github.com/getkirby/kirby/blob/6.0.0/src/Panel/Assets.php
- https://github.com/getkirby/kirby/blob/6.0.0/views/panel.php
