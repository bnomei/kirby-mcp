# Panel pattern: display field with formatter API

## Kirby 6 replacement for a legacy section

Kirby 5 custom sections and their `/sections/...` endpoints are removed. Port the preview to a `Kirby\Form\Field\DisplayField` class registered under `fields`, and a Vue 3 field component. It displays derived text without storing a value. For the base pattern see `kirby://kb/panel/reference-sections`.

## Implementation

1. Define cheap label/fallback/format settings as typed `#[Prop]` properties and getters. Use `props()` for initial computed data, merging parent props.
2. Put expensive formatting in `api(): array`, with an explicit POST method and a `format` pattern. Capture the field or use the action's `$this->field()`; do not mistake the API object for the field.
3. Send input to `this.endpoints.field + '/format'` with the Panel API client. Validate keys, sizes, permissions and language on the backend; never evaluate submitted PHP or query strings.
4. Debounce per input and ignore stale responses, so a slow old request cannot replace a newer preview. Cancel timers/listeners on unmount.
5. Refresh from the current content/language context and show loading, failure and fallback states. Keep preview state local; do not emit a stored input value from a display field.
6. Return plain text by default. If trusted formatting needs HTML, escape user substitutions with `HtmlString` helpers and render via `v-safe-html`; raw provider HTML is not trusted.

## Contract example

The plugin-defined endpoint receives a field-local payload such as `{ "title": "Example" }` and returns `{ "text": "Example — Site" }`. These are application keys, not a core formatter API. Use the field endpoint rather than hard-coding a page URL so nested and file/user/site contexts can work.

## Verification

- Switching language refreshes the preview without overwriting content.
- Rapid edits retain the newest response; separate inputs do not cancel one another accidentally.
- Restricted roles cannot invoke actions by bypassing the UI.
- Missing data and failed requests display a safe fallback.
- Saving the form writes no display-field value.

## Sources

- https://getkirby.com/releases/6/migration/plugin-developers/sections
- https://getkirby.com/releases/6/migration/plugin-developers/fields
- https://getkirby.com/releases/6/migration/plugin-developers/trusted-html
