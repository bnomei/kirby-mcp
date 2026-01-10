# Panel view props (pattern)

## Goal

Send backend data into a Panel view component via `props`.

## Inputs to ask for

- Data to preload vs fetch later via API
- Expected size/shape of the data (pagination needed?)
- Serialization rules (dates, users/pages/files)

## When to use

- Custom Panel areas with view routes.
- Views that need initial data without extra API calls.

## Pattern

1. Return `component` and `props` from the view action.
2. Add optional view keys like `breadcrumb` and `search` when needed.
3. Declare matching props in the Vue view component.
4. Use API endpoints for large or frequently changing data.

## Example

```php
return [
  'component' => 'k-reports-view',
  'title' => 'Reports',
  'props' => [
    'reports' => $reports,
  ],
];
```

```js
panel.plugin('vendor/reports', {
  components: {
    'k-reports-view': {
      props: {
        reports: Array,
      },
    },
  },
});
```

## MCP: Inspect/verify

- Verify plugin load: `kirby_plugins_index`
- Area reference: `kirby://extension/panel-areas`
- KB reference: `kirby://kb/panel/reference-areas`

## Gotchas

- Props must be JSON-serializable; convert CMS objects via `->toArray()`/`->panel()` or map to arrays.
- Keep props small; for large or live data, pass filters and fetch via API instead.

## Links

- https://getkirby.com/docs/reference/plugins/extensions/panel-areas
- https://getkirby.com/docs/reference/plugins/ui

## Version notes (K5/K6)

- K5: Vue 2 props handling; serialize only JSON-safe data.
- K6: Vue 3 props handling; keep props shallow for performance.
- K5 -> K6: recheck complex prop shapes and date serialization.
