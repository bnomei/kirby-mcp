# Panel searches (type: searches)

## What it is

Searches define the search types available in the Panel search dialog. You can add custom search types or override the core searches.

## PHP registration

```php
Kirby::plugin('vendor/todos', [
  'areas' => [
    'todos' => function () {
      return [
        'search' => 'pages',
        'searches' => [
          'todos' => [
            'label' => 'Todos',
            'icon' => 'check',
            'query' => function (string|null $query, int $limit, int $page) {
              return [
                'results' => [],
                'pagination' => [
                  'page' => $page,
                  'limit' => $limit,
                  'total' => 0,
                ],
              ];
            },
          ],
        ],
      ];
    },
  ],
]);
```

## Vue registration

Run searches from Vue with the Panel helper:

```js
import { usePanel } from 'kirbyuse';

const panel = usePanel();
const results = await panel.search('todos', 'Search query');
```

## Data flow (props/events/load)

- `query` receives `(query, limit, page)` and returns a results array plus pagination.
- Result items can include `text`, `link`, `info`, `icon`, `image`, and `uuid`.
- Set `search` in the area to control the default search type.

## Common UI components

- The Panel search dialog is built-in; custom searches only supply data.

## Gotchas

- The search key must match the type used in `panel.search()`.
- When overriding core searches, call `$kirby->core()->area('site')['searches']['pages']['query']` for fallback behavior.

## MCP: Inspect/verify

- Verify plugin load: `kirby_plugins_index`
- Extension reference: `kirby://extension/panel-search`

## Links

- https://getkirby.com/docs/reference/plugins/extensions/panel-search
- https://getkirby.com/docs/reference/plugins/extensions/panel-areas
- https://getkirby.com/docs/reference/plugins/ui

## Version notes (K5/K6)

- K5: Vue 2 Panel runtime; searches return Vue 2-friendly payloads.
- K6: Vue 3 Panel runtime; searches return Vue 3-friendly payloads.
- K5 -> K6: re-test custom searches and pagination shapes.
