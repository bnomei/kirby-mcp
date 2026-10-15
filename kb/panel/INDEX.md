# Kirby Panel extensions

Use Kirby 6 conventions for new Panel work: PHP field classes and Vue 3 components.

Existing built-in sections remain supported through automatic conversion; prefer `pagelist`/`filelist` for new lists and picker fields for stored selections. Custom sections require a port. Recipes teach current contracts; compatibility notes explain existing Kirby 5 code. Start upgrades at `kirby://kb/scenarios/83-migrate-kirby-5-to-6`.

## Tooling

- `kirby://kb/panel/tooling-kirbyup` - bundler, dev server, watch mode
- `kirby://kb/panel/tooling-kirbyuse` - composables, typings, bundling and Vue imports
- `kirby://kb/panel/tooling-bundling` - build step decisions and no-bundle setup

## References

- `kirby://kb/panel/reference-editor` - live editor, feedback and buttons
- `kirby://kb/panel/reference-runtime` - component, selection and request contracts
- `kirby://kb/scenarios/84-kirby-6-core-contracts` - PHP, serialization and HTTP
- `kirby://kb/panel/reference-areas`
- `kirby://kb/panel/reference-fields`
- `kirby://kb/panel/reference-sections`
- `kirby://kb/panel/reference-dialogs`
- `kirby://kb/panel/reference-dropdowns`
- `kirby://kb/panel/reference-searches`

## Patterns

- `kirby://kb/panel/patterns-view-props`
- `kirby://kb/panel/patterns-section-load`
- `kirby://kb/panel/patterns-dialog-flow`
- `kirby://kb/panel/patterns-component-reuse`
- `kirby://kb/panel/patterns-area-button-dialog` - area buttons with dialog actions
- `kirby://kb/panel/patterns-field-svg-options` - field options with SVG previews
- `kirby://kb/panel/patterns-field-map-geocode` - map field with geocoding
- `kirby://kb/panel/patterns-field-api-embed` - API-backed embed field
- `kirby://kb/panel/patterns-area-api-ops` - area view backed by API ops
- `kirby://kb/panel/patterns-section-format-api` - display-field formatter API
- `kirby://kb/panel/patterns-view-embed-iframe` - iframe embed view
- `kirby://kb/panel/patterns-field-drawer-barcode` - drawer editing with preview
- `kirby://kb/panel/patterns-area-multiview-auth` - multi-view area with auth

## Compatibility

- `kirby://kb/panel/compat-k5-k6-migration`

## Scenarios (Cookbook)

These scenarios live under `kb/scenarios/` and are linked here for discovery:

- `kirby://kb/scenarios/panel-area-basic`
- `kirby://kb/scenarios/panel-area-advanced`
- `kirby://kb/scenarios/panel-field-custom`
- `kirby://kb/scenarios/panel-section-custom`
- `kirby://kb/scenarios/panel-bundling-decisions`

## External references

- Panel UI: https://getkirby.com/docs/reference/plugins/ui
- Panel Lab: https://lab.getkirby.com/public/lab
- Panel source: https://github.com/getkirby/kirby/tree/main/panel
- kirbyup: https://github.com/johannschopplich/kirbyup
- kirbyuse: https://github.com/johannschopplich/kirbyuse (verify the Kirby-6-compatible prerelease and pin it)
- Bundling decision: https://getkirby.com/docs/cookbook/plugins/to-bundle-or-not-to-bundle
