# Panel pattern - section with formatter API

## Goal

Create a Panel section that loads server-side props, derives preview output in the client, and optionally formats text via a custom API endpoint.

## Inputs to ask for

- Content keys to read for preview fields
- Default fallback values and title separator
- Whether formatting should be server-controlled
- Throttle or debounce limits for API calls

## MCP tools/resources to use

- kirby://roots
- kirby_plugins_index
- kirby_routes_index
- kirby_blueprints_index
- kirby_blueprint_read
- kirby://extension/panel-sections

## Files to touch

- site/plugins/<plugin>/index.php
- site/plugins/<plugin>/src/extensions/sections.php
- site/plugins/<plugin>/src/extensions/api.php
- site/plugins/<plugin>/src/panel/components/<Section>.vue

## Implementation steps

1. Register a custom section and expose props via `load()` to resolve KQL or defaults.
2. Add an API endpoint that formats or sanitizes text for the preview.
3. In the section component, refresh props when language changes and re-run formatting.
4. Throttle formatter calls to avoid excessive requests.
5. Prefer kirbyuse for composables if you already use it, but keep the section logic Vue 2 compatible for K5.

## Examples

- `POST /__my-section__/format/title` returns `{ text: "..." }`.
- Section props resolve `{{ site.title }}` or page fields via KQL.
- Preview uses computed values with a fallback to the Panel view title.

## Panel JS (K5)

```js
// site/plugins/example-serp-section/src/panel/SectionPreview.vue
import { ref, toRefs, watch, useApi } from 'kirbyuse';

const SectionPreview = {
  props: {
    title: String,
    description: String,
    pageId: String,
  },
  setup(props) {
    const api = useApi();
    const { title, description, pageId } = toRefs(props);
    const titleProxy = ref('');
    const descriptionProxy = ref('');
    let timer = null;

    const throttledFormat = (prop, value) => {
      clearTimeout(timer);
      timer = setTimeout(() => format(prop, value), 250);
    };

    const format = async (prop, value) => {
      const response = await api.post(`__my-section__/format/${prop}`, {
        pageId: pageId.value,
        value,
      });
      const text = response.data && response.data.text ? response.data.text : value;
      if (prop === 'title') titleProxy.value = text;
      if (prop === 'description') descriptionProxy.value = text;
    };

    watch(title, (value) => throttledFormat('title', value));
    watch(description, (value) => throttledFormat('description', value));

    return { titleProxy, descriptionProxy };
  },
};

panel.plugin('example/serp-section', {
  sections: {
    'serp-preview': SectionPreview,
  },
});
```

## Verification

- Section updates when language changes.
- Formatter endpoint is called only when needed and uses throttling.
- Preview output matches the server formatting rules.

## Links

- https://getkirby.com/docs/reference/plugins/extensions/panel-sections
- https://github.com/johannschopplich/kirbyuse

## Version notes (K5/K6)

- K5: Vue 2 sections; kirbyuse targets Vue 2 and 3.
- K6: Vue 3 sections; ensure composables use the Vue 3 build.
- K5 -> K6: re-test section load and formatter endpoints.
