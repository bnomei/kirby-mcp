# DRAFT: Panel pattern - area view backed by API operations

## Goal

Build a Panel area with a dedicated view that lists items, triggers actions, and relies on custom API endpoints.

## Inputs to ask for

- Area name, icon, and route pattern
- API endpoints needed (list, create, status, stats)
- Authentication and CSRF needs for POST requests
- Download or export behavior (direct routes vs API responses)

## MCP tools/resources to use

- kirby://roots
- kirby_plugins_index
- kirby_routes_index
- kirby://extension/panel-areas
- kirby://extension/panel-dialogs

## Files to touch

- site/plugins/<plugin>/index.php
- site/plugins/<plugin>/src/areas/<area>.php
- site/plugins/<plugin>/src/components/<AreaView>.vue
- site/plugins/<plugin>/index.js

## Implementation steps

1. Register an area with a view action that returns a component and initial props.
2. Add API routes for list, create, and status checks using a plugin namespace.
3. In the view component, call `useApi().get()` and `useApi().post()` for operations and update local state.
4. Use `k-collection`, `k-button-group`, and dialogs for status and settings.
5. If downloads need a direct link, add a non-API route with a guarded key.
6. Prefer kirbyup for bundling the view; if shipping plain JS, keep the component small and dependency-light.

## Examples

- `GET /my-feature/items` returns a list and summary stats.
- `POST /my-feature/create` triggers a job and returns a status message.
- `GET /my-feature/settings-status` powers warning banners in the view.

## Panel JS (K5)

```js
// site/plugins/acme-api-ops/src/components/ApiOpsView.vue
import { ref, onMounted, useApi, usePanel } from 'kirbyuse';

const ApiOpsView = {
  props: {
    stats: Object,
  },
  setup() {
    const api = useApi();
    const panel = usePanel();
    const items = ref([]);
    const loading = ref(false);
    const creating = ref(false);

    const loadItems = async () => {
      loading.value = true;
      try {
        const response = await api.get('my-feature/items');
        items.value = response.data || [];
      } finally {
        loading.value = false;
      }
    };

    const createItem = async () => {
      creating.value = true;
      try {
        const response = await api.post('my-feature/create');
        panel.notification.success(response.message || 'Created');
        loadItems();
      } catch (error) {
        panel.notification.error('Create failed');
      } finally {
        creating.value = false;
      }
    };

    onMounted(loadItems);

    return { items, loading, creating, loadItems, createItem };
  },
};

panel.plugin('acme/api-ops', {
  components: {
    'acme-api-ops-view': ApiOpsView,
  },
});
```

## Verification

- Area appears in the Panel menu and loads with initial props.
- API operations return success and error notifications.
- Download links work without exposing unsafe routes.

## Links

- https://getkirby.com/docs/reference/plugins/extensions/panel-areas
- https://getkirby.com/docs/reference/plugins/extensions/api

## Version notes (K5/K6)

- K5: Vue 2 area views; use `useApi()` and `usePanel()` from kirbyuse.
- K6: Vue 3 area views; confirm CSRF headers for POST requests.
- K5 -> K6: re-check any bundled JS and view components.
