# Panel component and request contracts

Use Vue 3 components and the Panel's own Vue instance. These contracts complement `kirby://kb/panel/reference-fields`, `kirby://kb/panel/reference-areas` and `kirby://kb/panel/tooling-kirbyup`.

## Model IDs and previews

Picker fields exchange UUIDs/IDs with their backend and dialogs, not full display objects. Image/gallery/video block previews likewise receive file UUIDs. Resolve preview data when needed:

```js
const file = await this.$helper.items('items/files', 'file://abc');
const pages = await this.$helper.items('items/pages', ['page://a', 'page://b']);
```

IDs here are placeholders; supply actual authorized model IDs. Calls in the same tick are batched and list results follow requested order. Handle missing/inaccessible models without deleting their stored references. `k-image-frame` accepts a file ID/UUID via `file`; use ratio/cover settings for previews. Its images load lazily by default; set `:lazy="false"` for immediately visible images.

`k-collection`/`k-items` selection events pass an array of selected IDs. Keep the parent's selected list as IDs; do not treat the payload as a single item object. `k-item` supports `selected` and `selectmode`.

## Components and presentation

- Use `k-dropdown` and `align-x`, `k-state-dialog`, `k-state-drawer`, `k-tag`, `k-tags` and `k-tags-field-preview`.
- Use `k-model-picker-dialog` or `k-page-picker-dialog` for model picking. Use `k-model-form` for model forms, not section components.
- Supply `k-panel-menu` props explicitly and model-view props at the top level. `k-structure-drawer` expects boolean `prev`/`next`.
- Use toggles when a resettable choice is needed; radio inputs do not support `reset`.
- Use `k-text :text="value"` for text, and `v-safe-html` for intentionally trusted markup. `k-box` slot data contains `text`, not a separate `html` flag. `$helper.string.sanitizeHTML()` is asynchronous; await it before using the result. Trust wrappers do not sanitize.
- `k-table` is responsive by default; set `responsive: false` only when implementing your own narrow-width behavior. `k-progress` wraps its progress element in a label; list items contain `.k-item-box`.
- The icon sprite lives at `$panel.urls.icons`; hand-written SVG references need that URL plus the fragment. Prefer registered icons through core components.
- Target `.k-pagepicker-field`, `.k-filepicker-field`, `.k-userpicker-field` and current display-field classes. Detect overlays with `:has(.k-overlay[open])`.

## Requests, errors and areas

Use `window.panelState`, `X-Panel` headers and unprefixed response keys for Panel state integrations. `$panel.urls.panel` gives the Panel URL. Throw backend exceptions for failures; do not bury errors in a nested state object. `panel.notification.error()` receives errors, and request-error dialogs can show structured details.

Set an area definition to `false` to remove a core search, dialog, drawer, dropdown, request or view:

```php
Kirby::plugin('example/no-file-search', [
    'areas' => [
        'site' => ['searches' => ['files' => false]],
    ],
]);
```

Removing UI routes does not revoke underlying API permissions. Keep authorization checks in the owning backend operation.

Use controller `factory()`/`load()` for core route reuse. View-related behavior belongs on `ModelViewController` (`breadcrumb`, `buttons`, `prev`, `next`, `props`, `versions`, `load`), not Panel model adapters. UI namespaces are singular `Kirby\Panel\Ui\Button` and `FilePreview`; HTTP response classes live under `Kirby\Panel\Response`. `$kirby->panel()->areas()` returns an `Areas` collection, not an array.

## Existing Kirby 5 plugins

Replace `k-dropdown-content`, Fiber components/headers and bubble components with the names above. Replace `$helper.link.preview()` with model preview components or `$helper.items()`. The old global `pages/create` dialog is deprecated; use the `create` dialog belonging to a `pagelist` field.

Section APIs and components do not survive automatic blueprint conversion: use `field` rather than `section` in blueprint API queries, `endpoints.field`, and `field.loaded` for list loading events. Built-in section YAML still works; custom section code requires a field-class port.

Plugin assets are separate modules/stylesheets at hashed per-plugin URLs, not combined `media/plugins/index.js`/`index.css`. Top-level module variables are private to that plugin. Use supported imports or deliberate shared interfaces, not accidental global scope. `Plugins::css()`/`js()` expose asset URLs; do not call the removed `Plugins::url()`.

Replace removed v3 CSS variables with current tokens, e.g. `--color-red-600`, `--panel-color-back`, `--color-text-dimmed`, `--text-sm`; use explicit spacing where obsolete numbered tokens were removed. Check overrides against actual DOM and styles.

## Sources

- https://getkirby.com/releases/6/changelog#breaking-changes__panel
- https://getkirby.com/releases/6/migration/plugin-developers/vue-3
- https://getkirby.com/releases/6/migration/plugin-developers/panel-backend
