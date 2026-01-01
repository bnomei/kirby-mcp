# Panel extensions: K5 to K6 migration

## What changed

- Panel runtime moved from Vue 2.7 to Vue 3.
- `kirbyup` 3.x (Vue 2) became `kirbyup` 4.x (Vue 3).
- `kirbyuse` 1.x became `kirbyuse` 2.x and requires an import map.
- HMR is not yet available in `kirbyup` 4.x; use watch mode instead.

## Migration checklist

- Update `kirbyup` and `kirbyuse` versions.
- Replace Vue 2 syntax with Vue 3 compatible options.
- Add import map for `kirbyuse` in the Panel bootstrap.
- Verify `panel` asset paths match the new build output.
- Re-test custom fields, sections, areas, dialogs, dropdowns, and searches.

## MCP: Inspect/verify

- Runtime version: `kirby://info`
- Resolve paths: `kirby://roots`
- Verify plugin load: `kirby_plugins_index`
- Check dev mode: `kirby://config/panel.dev`
- Tooling docs: `kirby://kb/panel/tooling-kirbyup`, `kirby://kb/panel/tooling-kirbyuse`

## Links

- https://github.com/johannschopplich/kirbyup
- https://github.com/johannschopplich/kirbyuse
- https://getkirby.com/docs/reference/plugins/ui
- https://lab.getkirby.com/public/lab
- https://github.com/getkirby/kirby/tree/main/panel

## Version notes (K5/K6)

- K5: Vue 2 Panel runtime; `kirbyup` 3.x and `kirbyuse` 1.x.
- K6: Vue 3 Panel runtime; `kirbyup` 4.x and `kirbyuse` 2.x.
- K5 -> K6: confirm build output, import maps, and component compatibility.
