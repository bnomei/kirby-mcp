# Section load flow (pattern)

## Goal

Load section data asynchronously with `load()`.

## When to use

- Custom sections that need to fetch or compute data.
- Sections with computed props or API endpoints.

## Pattern

1. Define section `props` and `computed` in PHP.
2. Create a section component with `data()` keys matching props/computed returned by `load()`.
3. Call `useSection().load()` in `created()`/`onMounted()` and populate reactive refs.
4. When using Composition API, pass `parent` and `name` props (use `kirbyuse/props` `section` helper).

## Example

```js
import { ref, useSection } from 'kirbyuse';

panel.plugin('vendor/modified', {
  sections: {
    modified: {
      setup() {
        const label = ref(null);
        const text = ref(null);
        const { load } = useSection();

        load().then((response) => {
          label.value = response.label;
          text.value = response.text;
        });

        return { label, text };
      },
    },
  },
});
```

## MCP: Inspect/verify

- Section reference: `kirby://extension/sections`
- KB reference: `kirby://kb/panel/reference-sections`
- Blueprint usage: `kirby_blueprints_index` + `kirby_blueprint_read`

## Gotchas

- Sections are async: render placeholders until `load()` resolves.
- Response keys must exist in `data()`/refs before load to avoid undefined access.

## Links

- https://getkirby.com/docs/reference/plugins/extensions/sections
- https://getkirby.com/docs/reference/plugins/ui

## Version notes (K5/K6)

- K5: Vue 2 async lifecycle; use `created()`.
- K6: Vue 3 async lifecycle; `created()` still works for options API.
- K5 -> K6: re-test `load()` timing and error handling.
