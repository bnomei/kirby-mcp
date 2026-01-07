# Panel pattern - multi-view area with auth flow

## Goal

Create a Panel area that switches between multiple views (config, list, detail) and integrates with external APIs using custom routes.

## Inputs to ask for

- Area name, icon, and route patterns for each view
- Authentication flow (developer token, user token, refresh, revoke)
- External API endpoints, rate limits, and CORS requirements
- Which parts should be a field or block vs an area view

## MCP tools/resources to use

- kirby://roots
- kirby_plugins_index
- kirby_routes_index
- kirby://extension/panel-areas
- kirby://extension/panel-fields
- kirby://extension/panel-blocks

## Files to touch

- site/plugins/<plugin>/index.php
- site/plugins/<plugin>/lib/routes.php
- site/plugins/<plugin>/src/areas/<area>.php
- site/plugins/<plugin>/src/components/<View>.vue
- site/plugins/<plugin>/src/index.js

## Implementation steps

1. Register an area with multiple view routes (list and detail paths).
2. Add routes for auth, token storage, data fetch, and search under a namespaced prefix.
3. In the area view, call the routes with `useApi()`, including credentials and CSRF headers.
4. Switch the initial view based on config status and token availability.
5. Keep view components focused and reuse shared helpers for API calls and notifications.
6. Use kirbyup for bundling; if the example plugin is prebuilt, re-bundle for new work.

## Examples

- `GET /my-app/config-status` drives the initial view (config vs list).
- `GET /my-app/search?q=...` returns items with Panel links.
- `GET /my-app/item/:id` powers detail views.

## Panel JS (K5)

```js
// site/plugins/example-app/src/index.js
import { onMounted, ref, useApi, usePanel } from 'kirbyuse';

const ConfigView = {
  setup() {
    const api = useApi();
    const panel = usePanel();
    const loading = ref(true);
    const status = ref(null);
    const missing = ref([]);

    const authorize = () => {
      window.location.href = '/my-app/auth';
    };

    const disconnect = async () => {
      await api.post('my-app/delete-token');
      panel.notification.success('Disconnected');
    };

    onMounted(async () => {
      try {
        const response = await api.get('my-app/config-status');
        status.value = response.status;
        missing.value = response.missing || [];
      } finally {
        loading.value = false;
      }
    });

    return { loading, status, missing, authorize, disconnect };
  },
  template: `
    <k-view>
      <k-header>Configure</k-header>
      <k-box v-if="loading" icon="loader">Checking config...</k-box>
      <k-box v-else-if="status !== 'ok'" theme="negative">
        Missing: {{ missing.join(", ") }}
      </k-box>
      <k-button v-else icon="open" @click="authorize">Authorize</k-button>
    </k-view>
  `,
};

const ListView = {
  setup() {
    const api = useApi();
    const items = ref([]);
    const loading = ref(false);

    const fetchItems = async () => {
      loading.value = true;
      try {
        const response = await api.get('my-app/recent');
        items.value = response.data || [];
      } finally {
        loading.value = false;
      }
    };

    onMounted(fetchItems);

    return { items, loading };
  },
  template: `
    <k-view>
      <k-header>Recent</k-header>
      <k-box v-if="loading" icon="loader">Loading...</k-box>
      <k-collection v-else :items="items" layout="list" />
    </k-view>
  `,
};

panel.plugin('example/app', {
  components: {
    'k-app-config-view': ConfigView,
    'k-app-list-view': ListView,
  },
});
```

## Verification

- Area menu entry loads the correct view based on config status.
- Auth flow stores and revokes tokens correctly.
- List and detail views handle errors and show loading states.

## Links

- https://getkirby.com/docs/reference/plugins/extensions/panel-areas
- https://getkirby.com/docs/reference/plugins/extensions/api

## Version notes (K5/K6)

- K5: Vue 2 components and older Panel routing.
- K6: Vue 3 components; use kirbyup for new builds.
- K5 -> K6: re-test auth routes, CSRF, and view routing.
