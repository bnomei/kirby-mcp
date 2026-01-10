# Scenario: Add “columns” syntax to KirbyText via plugin

## Goal

Add a custom KirbyText syntax like:

```
(columns…)
Left column
+++
Right column
(…columns)
```

…and render it as HTML columns.

## Inputs to ask for

- Desired syntax markers and delimiter between columns
- HTML output structure and CSS requirements
- Whether this should be a KirbyTag instead (sometimes simpler)

## Internal tools/resources to use

- Inspect hooks: `kirby://hooks` / `kirby://hook/{name}`
- Inventory plugins: `kirby_plugins_index`
- Validate output: `kirby_render_page`

## Implementation steps

1. Create a plugin that registers a KirbyTags hook (e.g. `kirbytags:before`).
2. In the hook, `preg_replace_callback()` the custom `(columns…) ... (…columns)` blocks.
3. Split inner content into columns (e.g. on `++++` line separator).
4. Return HTML wrapper + column divs; add CSS.

## Examples (high-level from the recipe)

```php
Kirby::plugin('acme/columns', [
  'hooks' => [
    'kirbytags:before' => function (string $text, array $data = []) {
      return preg_replace_callback(
        '!\(columns(…|\.{3})\)(.*?)\((…|\.{3})columns\)!is',
        function ($matches) use ($data) {
          $columns = preg_split('!(\n|\r\n)\+{4}\s+(\n|\r\n)!', $matches[2]);
          $html = array_map(
            fn ($col) => '<div class="column">' . kirby()->kirbytext($col, $data) . '</div>',
            $columns
          );
          return '<div class="columns">' . implode('', $html) . '</div>';
        },
        $text
      );
    },
  ],
]);
```

## Verification

- Add the syntax to a KirbyText field and confirm it renders into the expected HTML.

## Glossary quick refs

- kirby://glossary/kirbytext
- kirby://glossary/hook
- kirby://glossary/plugin

## Links

- Cookbook: Columns in KirbyText: https://getkirby.com/docs/cookbook/extensions/columns-in-kirbytext
- Reference: KirbyTags hooks: https://getkirby.com/docs/reference/plugins/extensions/kirbytags
