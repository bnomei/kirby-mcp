# kirbyup (Panel bundler)

Use kirbyup 4 for Kirby 6 and its Vue 3 Panel. The released version is **4.0.0** and requires Node.js 24 or newer. A plugin targeting Kirby 4/5 still needs the legacy kirbyup 3 / Vue 2 build.

## When to use

- Build Panel plugin assets (JS/CSS) with a zero-config Vite-based bundler.
- Use HMR when available; fall back to watch-mode builds when HMR is unavailable.
- Add PostCSS, env-based conditionals, or custom Vite config in a small plugin.

## Minimal setup

Install the build dependency with `npm install --save-dev kirbyup@^4` and commit the lockfile. In the plugin's `package.json`:

```json
{
  "scripts": {
    "dev": "kirbyup dev src/index.js",
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

- `npm run dev` starts the HMR server and writes `index.dev.js` to point the Panel at it. The old `serve` subcommand still works as an alias for `dev`.
- For development without a server, `npm run build -- --watch` rebuilds `index.dev.js` and any `index.dev.css` without overwriting the production assets. Use `--watch-path src,assets` to replace the default watch paths: build accepts files/directories; dev also accepts globs.

## Build output

- Run `npm run build` to generate `index.js` and, if the plugin imports styles, `index.css` in the output directory. The production build removes leftover dev assets.
- Ignore `index.dev.*` in Git and exclude it from releases. A nonempty `index.dev.js` makes the Panel prefer development assets and load Vue's development build.

## Compatibility notes

- kirbyup exposes `import.meta.env.MODE`, `import.meta.env.PROD`, `import.meta.env.DEV`.
- `.env` and `.env.local` files are supported via Vite; only `KIRBYUP_` and `VITE_` prefixed variables are exposed.
- `kirbyup.config.js`/`kirbyup.config.ts` uses `defineConfig` from `kirbyup/config`.
- `alias` and `vite` are supported; replace the removed `extendViteConfig` key with `vite`.
- Built-in path aliases: `~/` and `@/`.

## MCP: Inspect/verify

- `kirby_roots` to confirm the plugin path under `site/plugins`.
- `kirby_plugins_index` to confirm the plugin is loaded at runtime.
- `kirby_blueprints_index` to find where Panel components are used.

## Links

- https://github.com/johannschopplich/kirbyup
- https://github.com/johannschopplich/kirbyup/blob/v4.0.0/docs/guide/migration.md
- https://kirbyup.getkirby.com

## Kirby 6 verification

Import Vue helpers by name from `vue`; the Panel provides the runtime, not a default export or `window.Vue`. Test both HMR and the production bundle with the dev server stopped. See https://getkirby.com/docs/guide/updates/update-to-v6/plugin-developers/vue-3.
