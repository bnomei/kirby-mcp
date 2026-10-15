# Component reuse (pattern)

## Goal

Reuse Panel UI components and core value/display field components instead of rebuilding them. Kirby 5 legacy custom sections must become display fields; see `kirby://kb/panel/reference-sections`.

## Inputs to ask for

- Installed Kirby 6 version (baseline beta.1)
- Core component or field you want to extend
- Build setup (kirbyup bundle vs plain JS)

## When to use

- You want consistent Panel styling and behavior.
- You need a field that is a small variation of a core one.

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
      emits: ['input'],
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
- Pair the component with a PHP field class; see `kirby://kb/panel/reference-fields`.
- KB references: `kirby://kb/panel/reference-fields`, `kirby://kb/panel/reference-sections`

## Gotchas

- Component names are kebab-case with `k-` prefix; blueprint field types are not component names.
- If you override `onInput`, always `$emit('input', value)` (or derived value) to keep the field reactive.

## Links

- https://getkirby.com/docs/reference/plugins/ui
- https://lab.getkirby.com/public/lab
- https://github.com/getkirby/kirby/tree/main/panel

## Kirby 6 migration

Rebuild legacy Vue 2 components for Vue 3. Kirby inputs retain `value`/`input`; do not blindly switch to `modelValue`/`update:modelValue`. Check core component methods against the installed tag.
