# Barcode field with drawer editing

## Contract

Use a PHP `TextField` subclass for the stored code and server-side validation for the selected barcode standard. Register typed display props and a Vue 3 field component with a string `value` and explicit `input` event. Preserve leading zeroes; barcode identifiers are strings, not numbers.

Choose the barcode standard, checksum rules, renderer and drawer fields before implementing. Length alone is not a barcode validator.

## Implementation

1. Add PHP validation for allowed characters, length and checksum. Decide whether an empty value is allowed. Keep a canonical string storage format.
2. Render a `k-field` wrapper with an edit button in `<template #options>`. Use a local draft value while the drawer is open; cancel must not emit or save.
3. Open the drawer with the Panel drawer API. For backend-defined drawers use the field's `drawers()` registration and `endpoints.field` path; register route factories as closures. Reuse `kirby://kb/panel/reference-dialogs` for route and submit conventions.
4. On successful submit, emit the validated code through `input`, close the drawer and let the parent form own content saving. Do not independently write page content from a field preview. Backend validation remains authoritative even if the client also validates.
5. Import a compatible barcode renderer with kirbyup 4. Render into a template-ref SVG/canvas only after mount/`nextTick`; watch external value changes, clear previews for empty values and display renderer errors without corrupting the saved code.
6. Clean up renderer state and listeners on unmount. Render user text as text; library-generated SVG is not a reason to trust arbitrary stored HTML. Preserve label, help, disabled state and keyboard access.

## Verification

Exercise valid/invalid checksums, leading zeroes, empty values, cancel, repeated open/close, reset/discard, mount timing and save/reload. Test backend rejection independently of client validation, including API writes. Confirm the field works in every supported model/nested placement.

## Existing Kirby 5 plugins

Replace array field definitions with a PHP class; use Vue helpers from `vue`, explicit emitted events and Vue 3 slots. Replace `slot="options"` with `<template #options>`. Do not render before refs exist or assume old drawer component internals remain unchanged.

## References

- `kirby://kb/panel/reference-fields`
- `kirby://kb/panel/reference-dialogs`
- https://getkirby.com/releases/6/migration/plugin-developers/fields
- https://getkirby.com/releases/6/migration/plugin-developers/vue-3
