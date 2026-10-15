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
            'action' => function (string|null $query, int $limit, int $page) {
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

In Options API you can also call `this.$search('todos', query)`.

## Data flow (props/events/load)

- The search action receives `(query, limit, page)` and returns results plus pagination; a `SearchController` class can also handle it.
- Result items can include `text`, `link`, `info`, `icon`, `image`, and `uuid`.
- Set `search` in the area to control the default search type.
- For simple searches, returning a flat array of results is acceptable; pagination is optional.

## Common UI components

- The Panel search dialog is built-in; custom searches only supply data.

## Gotchas

- The search key must match the type used in `panel.search()`.
- Kirby 5 legacy `query` overrides lose to the core Kirby 6 `action`. Override `action` and use `Kirby\Panel\Controller\Search\PagesSearchController` for fallback behavior rather than calling a removed core closure.
- Core search types are `site/pages`, `site/files`, and `users/users`.

## MCP: Inspect/verify

- Verify plugin load: `kirby_plugins_index`
- Extension reference: `kirby://extension/panel-search`

## Links

- https://getkirby.com/docs/reference/plugins/extensions/panel-search
- https://getkirby.com/docs/reference/plugins/extensions/panel-areas
- https://getkirby.com/docs/reference/plugins/ui

## Search checks

Test result shapes, pagination and controller fallback. Bundle kirbyuse and leave Vue external; see `kirby://kb/panel/tooling-kirbyuse`.
