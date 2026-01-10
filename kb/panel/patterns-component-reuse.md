# Component reuse (pattern)

## Goal

Reuse Panel UI components and core field/section components instead of rebuilding them.

## Inputs to ask for

- Panel version (K5 or K6)
- Core component/field/section you want to extend
- Build setup (kirbyup bundle vs plain JS)

## When to use

- You want consistent Panel styling and behavior.
- You need a field or section that is a small variation of a core one.

## Pattern

- Use Panel Lab to find component examples and props (Docs tab has prop lists).
- Confirm canonical component names and slots in `panel/src/components` in the Panel repo.
- Extend core field components with `extends` and keep the `input` event to update Panel state.
- Wrap area views in `k-panel-inside` or `k-inside` plus `k-view` for standard layout.

## Example

```js
panel.plugin('vendor/hello', {
  fields: {
    hello: {
      extends: 'k-text-field',
      methods: {
        onInput(value) {
          this.$emit('input', value.trim());
        },
      },
    },
  },
});
```

## MCP: Inspect/verify

- Field reference: `kirby://extension/fields`
- Section reference: `kirby://extension/sections`
- KB references: `kirby://kb/panel/reference-fields`, `kirby://kb/panel/reference-sections`

## Gotchas

- Component names are kebab-case with `k-` prefix; blueprint field types are not component names.
- If you override `onInput`, always `$emit('input', value)` (or derived value) to keep the field reactive.

## Links

- https://getkirby.com/docs/reference/plugins/ui
- https://lab.getkirby.com/public/lab
- https://github.com/getkirby/kirby/tree/main/panel

## Version notes (K5/K6)

- K5: Vue 2 component extension.
- K6: Vue 3 component extension.
- K5 -> K6: re-check component names in the Panel source.
