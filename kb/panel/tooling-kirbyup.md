# kirbyup (Panel bundler)

## When to use

- Build Panel plugin assets (JS/CSS) with a zero-config Vite-based bundler.
- Use HMR for Kirby 5 Panel development or watch-mode builds for Kirby 6.
- Add PostCSS, env-based conditionals, or custom Vite config in a small plugin.

## Minimal setup

```json
{
  "scripts": {
    "dev": "kirbyup serve src/index.js",
    "build": "kirbyup src/index.js"
  }
}
```

Optional config file (in plugin root):

```js
import { defineConfig } from 'kirbyup/config';

export default defineConfig({
  alias: {
    // absolute paths only
  },
  vite: {
    // merged into kirbyup defaults
  },
});
```

## Dev workflow

- `kirbyup serve src/index.js` starts the dev server and generates `index.dev.mjs` that points the Panel at the dev server.
- `kirbyup src/index.js --watch` builds dev bundles on file changes (useful for older Kirby versions or when HMR is unavailable).
- `--watch` (serve) defaults to `./**/*.php` and can be extended; `--no-watch` disables PHP watching.

## Build output

- Production build writes `index.js` and `index.css` to the output directory.
- Dev server creates `index.dev.mjs` as an entry that loads assets from the dev server.

## Compatibility notes

- kirbyup exposes `import.meta.env.MODE`, `import.meta.env.PROD`, `import.meta.env.DEV`.
- `.env` and `.env.local` files are supported via Vite; only `KIRBYUP_` and `VITE_` prefixed variables are exposed.
- `kirbyup.config.js`/`kirbyup.config.ts` uses `defineConfig` from `kirbyup/config`.
- `alias` and `vite` are supported; `extendViteConfig` is deprecated.

## MCP: Inspect/verify

- `kirby_roots` to confirm the plugin path under `site/plugins`.
- `kirby_plugins_index` to confirm the plugin is loaded at runtime.
- `kirby_blueprints_index` to find where Panel components are used.

## Links

- https://github.com/johannschopplich/kirbyup
- https://github.com/johannschopplich/kirbyup/tree/feat/vue-3
- https://kirbyup.getkirby.com

## Version notes (K5/K6)

- K5: `kirbyup` 3.x (main branch) uses Vue 2.7 with `@vitejs/plugin-vue2` and supports HMR via `kirbyup serve`.
- K6: `kirbyup` 4.x (feat/vue-3, `v4.0.0-alpha.5`) uses Vue 3 with `@vitejs/plugin-vue`. HMR is not implemented yet; use `kirbyup build <entry> --watch` for development.
- K5 -> K6: upgrade to `kirbyup` 4.x, switch to Vue 3-based Panel tooling, and replace `serve` with watch-mode builds until HMR lands.
