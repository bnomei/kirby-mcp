# kirbyuse for Kirby 6 Panel plugins

kirbyuse provides composables and typings. Use its Kirby-6/Vue-3 prerelease line, not the Kirby 5 legacy 1.x package. The official beta.1 migration guide points to the `next` tag; verify and pin the package version in your lockfile. The inspected `feat/kirby-6` source declares `2.0.0-beta.2`; that source version alone is not proof of an npm dist-tag.

## Build setup

Bundle kirbyuse into the plugin JavaScript and leave `vue` external. Kirby `6.0.0-beta.1` provides an import map for `vue` only. It does **not** register plugin `importMaps` or invoke a `panel.extend` callback to modify assets. Earlier speculative examples of those hooks must not be used for this tag.

```js
import { ref } from 'vue';
import { usePanel, useContent } from 'kirbyuse';
```

The example is build input. Do not leave a bare browser import from `kirbyuse` unless you have separately verified a supported mapping mechanism for your installed version. Do not bundle Vue itself: the Panel and plugin must share one runtime.

Kirby automatically loads a registered plugin's root `index.js` and optional `index.css`:

```php
Kirby::plugin('example/plugin', []);
```

For no-build plugins, prefer the Panel's built-in APIs and direct imports from `vue` instead of inventing a kirbyuse loader. See `kirby://kb/panel/tooling-bundling`.

## Migration checks

- Replace legacy section composables with field props or `endpoints.field` requests; custom sections no longer exist. See `kirby://kb/panel/patterns-section-load`.
- Remove legacy `useStore` and Kirby 4/5 compatibility helpers rather than keeping a parallel old runtime.
- Check the exact package's exports/types before using helpers; a 1.x recipe is not evidence that a 2.x helper still exists.
- Verify production asset loading, one Vue runtime, field input events and cleanup of watchers/listeners.

## Sources

- https://getkirby.com/releases/6/migration/plugin-developers/vue-3
- https://github.com/johannschopplich/kirbyuse/tree/feat/kirby-6
- https://github.com/getkirby/kirby/blob/6.0.0-beta.1/src/Panel/Assets.php
- https://github.com/getkirby/kirby/blob/6.0.0-beta.1/src/Panel/Response/ViewDocumentResponse.php
