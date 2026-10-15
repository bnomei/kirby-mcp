# Map and geocoding field

## Contract

Use a PHP `InputField` subclass with explicit structured conversion, or an `ObjectField` subclass with matching subfields. Define a location value such as `{ lat, lon, address, city, country, zoom }`. Register a Vue 3 component with an object `value` and `emits: ['input']`.

Choose the map/geocoding provider, attribution, token visibility, coordinate precision and whether zoom is saved. Keep secret tokens in backend configuration; only deliberately public, origin-restricted map tokens may reach the browser.

## Implementation

1. Implement stored YAML→form object→stored YAML conversion. Validate finite latitude in −90…90 and longitude in −180…180. Treat zero as a valid coordinate and null/empty as no location; do not use truthiness to detect a marker.
2. Import the map library and its CSS through kirbyup 4. Use a template ref for the map element, initialize on mount and destroy the instance/listeners in `beforeUnmount()` or `onBeforeUnmount()`. Do not use private Vue identifiers or assume a global `L`.
3. Hydrate the initial marker and watch external value changes (including reset/discard). Emit a new object only for editor actions; avoid a watch→emit loop. Resize the map after a hidden tab or collapsed field becomes visible.
4. Send searches through an authorized field `api()` endpoint when secrets are required. Use `endpoints.field`, allowlisted provider URLs, timeouts and rate limits. Debounce searches and ignore stale responses after newer input, marker dragging or unmount.
5. On result selection, update coordinates and address together. On marker drag, either reverse-geocode or clear the now-stale address. Preserve unrelated value properties and optional zoom consistently.
6. Show empty, loading, no-result and error states without silently erasing a saved location. Bind Kirby inputs with `:value`/`@input`; do not mutate `value` directly.

## Verification

Test latitude/longitude zero, negative coordinates, bounds, malformed stored strings, initial hydration, reset, search/drag races, denied API calls and navigation cleanup. Verify tiles and attribution, then save/reload the exact object. Verify hidden-tab sizing and keyboard alternatives to dragging.

## Existing Kirby 5 plugins

Port array-based PHP fields to classes and rebuild Vue 2/map wrappers for Vue 3. Replace `this._uid` with a template ref, add unmount cleanup, and replace `if (value.lat)` with explicit coordinate validation. Keep content keys stable and migrate incompatible value shapes deliberately.

## References

- `kirby://kb/panel/reference-fields`
- `kirby://kb/panel/tooling-kirbyup`
- https://getkirby.com/releases/6/migration/plugin-developers/vue-3
