# Panel section (Kirby 5 legacy)

Kirby 6 replaces sections with fields. Use `pagelist`/`filelist` for page/file listings and `info`/`stats` for informational displays. These fields store no content. Existing built-in sections auto-convert; custom plugin sections do not.

For a custom display, extend `Kirby\Form\Field\DisplayField`, register the class under PHP `fields` and the Vue 3 component under JavaScript `fields`. Replace section `this.load()` with direct field props for cheap data or a field `api()` endpoint for expensive data. See `kirby://kb/panel/reference-sections` and `kirby://kb/panel/reference-fields` for implementation.

Do not use `pagelist` as a replacement for a legacy `pages` picker field: the value-storing replacement is `pagepicker`.

## Links

- https://getkirby.com/releases/6/migration/plugin-developers/sections
- https://getkirby.com/releases/6/blueprints
