# Panel extensions: Kirby 5 to Kirby 6 migration

Baseline: **6.0.0-beta.1**, checked October 15, 2026. APIs/tooling remain beta-sensitive. The full site checklist is `kirby://kb/scenarios/83-migrate-kirby-5-to-6`. Do not deploy a beta migration just because assets compile.

## PHP fields and sections

Array-based Kirby 5 fields are removed. Register a PHP class under `fields`, choosing `DisplayField`, `ValueField` or `InputField` under `Kirby\Form\Field`. Replace props closures with typed `#[Prop]` properties/getters, `computed` with getters, `save` with `toStoredValue()` and `api`/`routes()` with `api()`. A direct value-field subclass needs a typed `$value` default. See `kirby://kb/panel/reference-fields`.

Custom sections are removed, not auto-converted. Port them to `DisplayField`, register Vue components under `fields`, and replace `this.load()` with cheap props or a field API route. Built-in sections alone auto-convert. See `kirby://kb/panel/reference-sections` and `kirby://kb/panel/patterns-section-load`.

## Vue 3 and ESM

- Rebuild all Vue 2 bundles with kirbyup 4 (the `next` release line at beta.1, Node.js 24+). Use `kirbyup dev src/index.js` for HMR, not legacy `serve`. Never ship `index.dev.js`; it replaces legacy `index.dev.mjs`.
- Import Vue from `vue`; Kirby provides the import map and runtime. Never bundle a second Vue copy or access `window.Vue`.
- Bundle Kirby-6-compatible kirbyuse into your plugin if used, leaving Vue external. **Tagged beta.1 has no plugin `importMaps` extension or `panel.extend` assets callback.** Do not paste an unverified import-map hook. See `kirby://kb/panel/tooling-kirbyuse`.
- Kirby fields still use `value` and `input`. Use explicit `:value`/`@input`, not default Vue 3 component `v-model`; declare `emits: ['input']`.
- Replace `$listeners` with `$attrs`, `$set`/`$delete` with assignment/deletion, `beforeDestroy`/`destroyed` with `beforeUnmount`/`unmounted`, and Vue-instance event buses with `$panel.events`. Unsubscribe on unmount.
- Vue plugin `install()` receives an app, not the Vue constructor. Use `app.config.globalProperties` and `app.component()`.
- Recheck third-party Vue libraries, Panel component names, CSS classes and slots against the installed tag; there is no Vue 2 compatibility runtime.

## Trusted HTML

Panel text props escape ordinary strings. Prefer text; when markup is needed, create `Kirby\Toolkit\HtmlString` only from trusted markup with escaped interpolated user data. `HtmlString::translate()` and `$model->toSafeHtmlString()` escape substitutions. Frontend `$h()` marks trust but does not sanitize; `$th()` escapes translation substitutions. Use `v-safe-html` instead of `v-html` for the same trust contract. Never mark raw provider HTML, SVG or user content trusted to make a preview work.

## Panel backend and controllers

Use `$kirby->panel()` for instance APIs (`url()`, `areas()`, `access()`, `isStateRequest()`); legacy static calls like `Panel::url()` and `Panel::hasAccess()` no longer apply. `Panel::go()` remains a documented redirect helper: do not mechanically convert every static call.

Area closures and `component`/`props` arrays still work. New controllers extend the matching `Kirby\Panel\Controller\ViewController`, `DialogController`, `DrawerController`, `DropdownController`, `SearchController` or `RequestController`. Register their class as `action`; `factory()` resolves route parameters, `load()` returns UI, and dialog/drawer `submit()` handles input. UI objects live in `Kirby\Panel\Ui`. Do not assume the old `controller`/`::for()` contract.

Core route definitions now contain controller classes. Call the matching controller's `factory()->load()` rather than invoking a core array closure. To override a core search, set `action`: legacy `query` loses to an existing core `action`. Dialog/drawer `load`/`submit` overrides still take precedence. The former “Fiber” response is now Panel state. Test authorization on backend routes, not just menu visibility.

## Authentication and sessions

Custom methods extend `Kirby\Auth\Method`; challenges extend `Kirby\Auth\Challenge`. Register via `authMethods`/`authChallenges`, then enable them in config. The legacy Panel `login` extension and `Kirby\Cms\Auth\Challenge` base are removed. `form()` supplies a Panel UI component for each step.

Challenge `create()` returns `Pending` data; hash secrets yourself before storing them in `Pending::secret`. `public` data is sent to the Panel. Implement `verify()`; distinguish login, password-reset and 2FA modes. `isAvailable($user, '2fa')` means the factor is set up and can force a second step, so make enrollment deliberate. Keep rate limiting, timeout and recovery paths; do not expose secrets in props or logs.

Frontend flows use `authenticate()` and `Status`/`State`, not string status checks. Basic Auth cannot complete 2FA. Old cookies are rejected, and session handler APIs changed; see the site guide and `kirby://glossary/session`.

## Verify

Test field value round-trips and non-value displays; asynchronous failure/empty states; nested field endpoints; dialogs/drawers; core-route overrides; allowed/denied roles; login challenges; and untrusted strings containing HTML. Inspect browser console warnings and the production bundle, not only a development build.

## Sources

- https://getkirby.com/releases/6/migration/plugin-developers
- https://getkirby.com/releases/6/migration/plugin-developers/vue-3
- https://getkirby.com/releases/6/migration/plugin-developers/fields
- https://getkirby.com/releases/6/migration/plugin-developers/sections
- https://getkirby.com/releases/6/migration/plugin-developers/trusted-html
- https://getkirby.com/releases/6/migration/plugin-developers/auth
- https://getkirby.com/releases/6/migration/plugin-developers/panel-backend
- https://github.com/getkirby/kirby/blob/6.0.0-beta.1/src/Panel/Assets.php
