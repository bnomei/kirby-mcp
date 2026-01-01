# Panel area

## Meaning

A Panel area is a top-level Panel app registered via the `areas` plugin extension. It defines routes (views), menu entry, and optional dialogs, dropdowns, and searches.

## In prompts (what it usually implies)

- "Add a Panel area" means create a plugin with an `areas` definition and a view route.

## Variants / aliases

- area id (slug)
- view routes within an area

## Example

```php
Kirby::plugin('vendor/todos', [
  'areas' => [
    'todos' => function () {
      return [
        'label' => 'Todos',
        'views' => [
          [
            'pattern' => 'todos',
            'action' => function () {
              return [
                'component' => 'k-todos-view',
              ];
            },
          ],
        ],
      ];
    },
  ],
]);
```

## MCP: Inspect/verify

- `kirby_plugins_index`
- `kirby://extension/panel-areas`
- `kirby://kb/panel/reference-areas`

## Related terms

- kirby://glossary/panel
- kirby://glossary/plugin
- kirby://glossary/route

## Links

- https://getkirby.com/docs/reference/plugins/extensions/panel-areas
