# Scenario: Build a custom display field

## Goal

Show computed data, a dashboard or a custom listing without storing content. Use `DisplayField` and register it under `fields`. For existing Kirby 5 custom sections, the same pattern is the required port; only built-in section configurations auto-convert.

## Implementation

1. Inspect the original blueprint and plugin with `kirby_blueprint_read` and `kirby_plugins_index`. Check whether a built-in `info`, `stats`, `pagelist`, `filelist` or `buttons` field already meets the need.
2. For custom UI, follow the PHP class and Vue registration in `kirby://kb/panel/reference-sections`: extend `Kirby\Form\Field\DisplayField`, register it under `fields` in both PHP and JavaScript.
3. Replace props/computed closures with typed `#[Prop]` properties/getters or `props()` merged with parent props. Compute cheap data lazily; move expensive work into `api()`.
4. Replace `this.load()` with direct props or fetch `this.endpoints.field`; see `kirby://kb/panel/patterns-section-load`.
5. Place the component under blueprint `fields`, use `label` instead of `headline`, and check case-insensitive name collisions with stored fields.
6. Rebuild for Vue 3 with kirbyup 4. Display trusted text; use explicit trusted-HTML APIs only after escaping user data.

## Verification

Test allowed and denied roles, loading/error/empty states and multiple models/languages. Saving the surrounding form must not create a stored value for this display. Runtime `fieldSchemas` should mark it `hasValue: false`. Endpoint authorization belongs on the backend even when a button is hidden.

## Links

- `kirby://kb/scenarios/panel-section-custom`
- `kirby://kb/panel/reference-fields`
- https://getkirby.com/releases/6/migration/plugin-developers/sections
