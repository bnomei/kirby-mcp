# Panel pattern - field with API-backed embed preview

## Goal

Provide a URL field that fetches embed metadata via a plugin API endpoint and renders a live preview in the Panel.

## Inputs to ask for

- Allowed providers or URL patterns
- Whether to store only the URL or a structured payload (url + metadata)
- Privacy options (for example nocookie variants)
- Script loading needs (Twitter, Instagram, etc.)

## MCP tools/resources to use

- kirby://roots
- kirby_plugins_index
- kirby_routes_index
- kirby_blueprints_index
- kirby_blueprint_read
- kirby://extension/api
- kirby://extension/fields

## Files to touch

- site/plugins/<plugin>/index.php
- site/plugins/<plugin>/lib/api.php (or equivalent)
- site/plugins/<plugin>/src/components/<EmbedField>.vue
- site/plugins/<plugin>/index.js

## Implementation steps

1. Create a plugin API route (via the `api` extension) that accepts a URL and returns parsed embed metadata.
   - If the endpoint should be scoped to the field, define a field `api` endpoint instead (auto-prefixed with `/fields/<fieldName>`).
2. Store a structured field value (input URL plus media payload) to avoid re-fetching on reload.
3. In the field component, call `useApi().get()` when the URL changes and update the stored value.
4. Render a preview and load provider scripts only when needed.
5. If the example plugin is prebuilt, keep the core behavior and re-bundle with kirbyup for new work.

## Examples

- API endpoint namespace: `/api/my-embed/get-data` with `{ url }` input.
- Field value shape: `{ input: "...", media: { title, code, providerName } }`.
- Preview shows a status badge for synced vs failed.

## Panel JS (K5)

```js
// site/plugins/example-embed-field/src/components/EmbedField.vue
import { computed, ref, useApi } from 'kirbyuse';

const EmbedField = {
  extends: 'k-url-field',
  props: {
    provider: String,
  },
  setup(props, { emit }) {
    const api = useApi();
    const media = ref({});
    const loading = ref(false);
    const inputValue = computed(() => (props.value && props.value.input ? props.value.input : ''));
    const hasMedia = computed(() => media.value && media.value.code);

    const emitInput = (url) => {
      emit('input', { input: url, media: media.value });
      loadProviderScripts();
    };

    const onInput = async (url) => {
      if (!url) {
        media.value = {};
        return emitInput(url);
      }
      loading.value = true;
      try {
        const response = await api.get('my-embed/get-data', { url });
        media.value = response.data || {};
      } catch (error) {
        media.value = {};
      } finally {
        emitInput(url);
        loading.value = false;
      }
    };

    const loadProviderScripts = () => {
      if (media.value.providerName === 'Twitter' && !window.twttr) {
        const script = document.createElement('script');
        script.src = 'https://platform.twitter.com/widgets.js';
        document.body.appendChild(script);
      }
    };

    return { media, loading, inputValue, hasMedia, onInput };
  },
};

panel.plugin('example/embed-field', {
  fields: {
    embed: EmbedField,
  },
});
```

## Verification

- URL changes trigger a single API call and update the preview.
- Value is persisted as structured YAML/JSON in the content file.
- Provider scripts load only when required.

## Links

- https://getkirby.com/docs/reference/plugins/extensions/fields
- https://getkirby.com/docs/reference/plugins/extensions/api

## Version notes (K5/K6)

- K5: Vue 2 field component with `useApi()` from kirbyuse.
- K6: Vue 3 field component; confirm the API endpoint still resolves.
- K5 -> K6: re-test preview rendering and provider script loading.
