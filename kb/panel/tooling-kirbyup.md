# kirbyup (Panel bundler)

Kirby 6 beta.1 baseline: kirbyup 4 is published under `next` and requires Node.js 24+. Verify and lock the installed prerelease. Kirby 5 legacy kirbyup 3/`serve` builds must be replaced with a Vue 3 build, not reused.

## When to use

- Build Panel plugin assets (JS/CSS) with a zero-config Vite-based bundler.
- Use HMR when available; fall back to watch-mode builds when HMR is unavailable.
- Add PostCSS, env-based conditionals, or custom Vite config in a small plugin.

## Minimal setup

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

- `npx kirbyup@next dev src/index.js` starts the HMR dev server and generates `index.dev.js` that points the Panel at the dev server.
- `kirbyup src/index.js --watch` builds dev bundles on file changes (useful for older Kirby versions or when HMR is unavailable).
- Confirm watch/config flags against the installed kirbyup 4 CLI; do not assume kirbyup 3 flags are unchanged.

## Build output

- Production build writes `index.js` and `index.css` to the output directory.
- Dev server creates `index.dev.js`. Ignore it in Git and never ship it; its presence also enables Vue's development build in the Panel.

## Compatibility notes

- kirbyup exposes `import.meta.env.MODE`, `import.meta.env.PROD`, `import.meta.env.DEV`.
- `.env` and `.env.local` files are supported via Vite; only `KIRBYUP_` and `VITE_` prefixed variables are exposed.
- `kirbyup.config.js`/`kirbyup.config.ts` uses `defineConfig` from `kirbyup/config`.
- `alias` and `vite` are supported; `extendViteConfig` is deprecated.
- Built-in path aliases: `~/` and `@/`.

## MCP: Inspect/verify

- `kirby_roots` to confirm the plugin path under `site/plugins`.
- `kirby_plugins_index` to confirm the plugin is loaded at runtime.
- `kirby_blueprints_index` to find where Panel components are used.

## Links

- https://github.com/johannschopplich/kirbyup
- https://github.com/johannschopplich/kirbyup/tree/feat/vue-3
- https://kirbyup.getkirby.com

## Kirby 6 verification

Build with `npx kirbyup@next src/index.js`; import Vue from `vue` and do not bundle a second runtime. Test both production and HMR output. See https://getkirby.com/releases/6/migration/plugin-developers/vue-3 for the beta tooling baseline.
