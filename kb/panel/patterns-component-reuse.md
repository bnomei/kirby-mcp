# Component reuse (pattern)

## Goal

Reuse Panel UI components and core field/section components instead of rebuilding them.

## When to use

- You want consistent Panel styling and behavior.
- You need a field or section that is a small variation of a core one.

## Pattern

- Use Panel Lab to find component examples and props.
- Extend core field components with `extends`.
- Wrap area views in `k-inside` and `k-view` for standard layout.

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

## Links

- https://getkirby.com/docs/reference/plugins/ui
- https://lab.getkirby.com/public/lab
- https://github.com/getkirby/kirby/tree/main/panel

## Version notes (K5/K6)

- K5: Vue 2 component extension.
- K6: Vue 3 component extension.
- K5 -> K6: re-check component names in the Panel source.
