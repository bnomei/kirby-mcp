# Panel pattern - simple view that embeds an iframe

## Goal

Add a lightweight Panel view that embeds an external dashboard in an iframe and pulls a signed or shared URL from a plugin API.

## Inputs to ask for

- View label and icon
- How the embed URL is produced (static config vs API lookup)
- Theme or query params to apply to the embed
- Dimensions and scroll behavior for the iframe

## MCP tools/resources to use

- kirby://roots
- kirby_plugins_index
- kirby_routes_index
- kirby://extension/api
- kirby://extension/panel-areas

## Files to touch

- site/plugins/<plugin>/index.php
- site/plugins/<plugin>/index.js
- site/config/config.php (embed link and theme options)

## Implementation steps

1. Register a view in the area definition (PHP) and point it to a component name.
   - Register that component in JS via `panel.plugin({ components: { ... }})`.
2. Add an API endpoint (via the `api` extension) that returns the final iframe URL.
3. In the view component, call `useApi().get()` during setup and bind the URL to the iframe.
4. Show a fallback message when the URL is missing or invalid.
5. Use kirbyup if you need a real component; inline templates can be shipped as plain JS.

## Examples

- `GET /api/analytics` returns a share URL with query params appended.
- The view renders an iframe only after the URL is loaded.
- Missing config shows a bold warning in the view.

## Panel JS (K5)

```js
// site/plugins/example-analytics/index.js
import { ref, useApi } from 'kirbyuse';

panel.plugin('example/analytics', {
  components: {
    'k-analytics-view': {
      setup() {
        const api = useApi();
        const embedUrl = ref('');

        api.get('analytics').then((url) => {
          embedUrl.value = url;
        });

        return { embedUrl };
      },
      template: `
        <k-view>
          <k-header>Analytics</k-header>
          <strong v-if="!embedUrl">Missing embed URL</strong>
          <iframe
            v-else
            :src="embedUrl"
            style="width: 1px; min-width: 100%; height: 1200px;"
            frameborder="0"
            scrolling="no"
          ></iframe>
        </k-view>
      `,
    },
  },
});
```

## Verification

- View loads without errors and shows a placeholder before the URL is fetched.
- Embed renders with the expected theme and size.
- Missing config is handled gracefully.

## Links

- https://getkirby.com/docs/reference/plugins/extensions/panel-areas
- https://getkirby.com/docs/reference/plugins/extensions/api

## Version notes (K5/K6)

- K5: Inline view components are common; keep Vue 2 syntax.
- K6: Vue 3 view components; consider moving to SFCs with kirbyup.
- K5 -> K6: rebuild any prebuilt JS for the new runtime.
