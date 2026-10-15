# API-backed embed field

## Contract

Use a PHP `InputField` subclass with an explicit structured value, or an `ObjectField` subclass whose nested form matches that value. Register it under `fields` and pair it with a Vue 3 component. Store the input URL and approved metadata; a `UrlField` cannot represent an object payload correctly. Follow `kirby://kb/panel/reference-fields` for props, storage and registration.

Choose allowed providers, privacy/consent requirements and whether metadata is persisted or fetched on demand before implementing. Do not store provider credentials in the value or exported props.

## Implementation

1. Define the value contract, for example `{ input, media: { title, providerName, thumbnailUrl } }`. Implement conversion between stored YAML and this object, including empty and invalid old values. Preserve unrelated properties when updating metadata.
2. Define a field-local `api()` route. Validate the URL's scheme, host and redirects before making any request; protect against SSRF, including private network destinations. Bound response size and time. Authorize access independently of field visibility.
3. Declare an object `value`, `endpoints`, and `emits: ['input']` in the component. Bind Kirby input components with `:value`/`@input`. Call `this.$api.get(this.endpoints.field + '/preview', { url })` rather than constructing a page-only endpoint.
4. Initialize the preview from `value.media`. Debounce input, assign each request a sequence number and ignore responses from older requests. Invalidate pending responses on clear and unmount. Only the current request may set loading/error state or emit a new value.
5. Render metadata as text. For a provider player, use an allowlisted iframe origin with appropriate sandbox/permissions and consent. Provider HTML is not trusted: sanitize or isolate it, never pass raw HTML to `$h()` or `HtmlString`.
6. Load third-party scripts only after consent and only for providers that require them. Share script loading deliberately, and destroy field-owned widgets/listeners on unmount. Handle fetch failure without silently replacing a previously saved value.
7. Build with kirbyup 4. Import Vue helpers from `vue`; bundle compatible kirbyuse only if needed, leaving Vue external.

## Verification

Exercise initial hydration, empty input, rapid A→B URL changes with A returning last, rejected URLs/redirects, denied access, failed providers, consent revocation and navigation cleanup. Save/reload the structured value and inspect the content file. Check site/page/file/user and nested placements that the plugin supports.

## Existing Kirby 5 plugins

Replace array-based PHP fields with classes. Replace URL-field inheritance when the stored value is an object. Vue 2 bundles and implicit component events need rebuilding; keep the content key stable and explicitly migrate incompatible old payloads. A rebuild alone does not migrate PHP storage or provider security.

## References

- `kirby://kb/panel/reference-fields`
- `kirby://kb/panel/tooling-kirbyup`
- https://getkirby.com/releases/6/migration/plugin-developers/fields
- https://getkirby.com/releases/6/migration/plugin-developers/trusted-html
