# Scenario: Advanced Panel area (dialogs, dropdowns, searches)

## Goal

Build a Panel area with multiple views and advanced UI pieces like dialogs, dropdowns, and searches.

## Inputs to ask for

- Plugin id (`vendor/name`)
- Area id and menu label/icon
- Data source (JSON file, API, database)
- Required dialogs (create, update, delete)
- Search behavior and sortable columns

## Internal tools/resources to use

- Resolve project paths: `kirby://roots`
- Confirm plugin is loaded: `kirby_plugins_index`
- Check `panel.dev` if needed: `kirby://config/panel.dev`

## Implementation steps

1. Create a structured plugin layout with separate PHP files for views, dialogs, dropdowns, and searches.
2. Register the area in `index.php` and `require` the sub-files.
3. Create view routes that return `component` + `props`.
4. Build Vue components for views and reuse Panel UI components.
5. Add dropdowns and dialogs in PHP and reference them from the view.
6. Build or serve the assets with `kirbyup`.

## Examples

### Suggested plugin layout

```filesystem
products/
  dialogs/
    delete.php
    update.php
  dropdowns/
    product.php
  searches/
    products.php
  views/
    products.php
  src/
    components/
      Products.vue
    index.js
  index.php
  package.json
  products.json
```

### Plugin registration (PHP)

`site/plugins/products/index.php`

```php
<?php

Kirby::plugin('vendor/products', [
  'areas' => [
    'products' => [
      'label' => 'Products',
      'icon'  => 'cart',
      'menu'  => true,
      'dialogs' => [
        require __DIR__ . '/dialogs/update.php',
        require __DIR__ . '/dialogs/delete.php',
      ],
      'dropdowns' => [
        require __DIR__ . '/dropdowns/product.php',
      ],
      'searches' => [
        require __DIR__ . '/searches/products.php',
      ],
      'views' => [
        require __DIR__ . '/views/products.php',
      ],
    ],
  ],
]);
```

### View route (PHP)

`site/plugins/products/views/products.php`

```php
<?php

return [
  'pattern' => 'products',
  'action' => function () {
    return [
      'component' => 'k-products-view',
      'props' => [
        'products' => [],
      ],
    ];
  },
];
```

### View component (Vue)

`site/plugins/products/src/components/Products.vue`

```vue
<template>
  <k-inside>
    <k-view>
      <k-header>Products</k-header>
      <table class="k-products">
        <tr v-for="(product, id) in products" :key="id">
          <td>{{ product.title }}</td>
          <td class="k-product-options">
            <k-options-dropdown :options="'products/' + id" />
          </td>
        </tr>
      </table>
    </k-view>
  </k-inside>
</template>

<script>
export default {
  props: {
    products: Object,
  },
};
</script>
```

## Verification

- Build or serve assets with `kirbyup`.
- Confirm the menu entry appears and routes resolve.
- Trigger dropdowns/dialogs and verify API responses.
- Use `kirby_plugins_index` to confirm the plugin is loaded.

## Glossary quick refs

- kirby://glossary/panel
- kirby://glossary/kirbyuse
- kirby://glossary/plugin
- kirby://glossary/route

## Links

- Cookbook: https://getkirby.com/docs/cookbook/panel/advanced-panel-area
- Panel areas: https://getkirby.com/docs/reference/plugins/extensions/panel-areas
- Panel tooling: kirby://kb/panel/tooling-kirbyup

## Version notes (K5/K6)

- K5: Vue 2 Panel runtime; `kirbyup` 3.x supports HMR for rapid iteration.
- K6: Vue 3 Panel runtime; `kirbyup` 4.x uses watch-mode builds while HMR is unavailable.
- K5 -> K6: update Vue components to Vue 3-compatible syntax and revisit any Panel UI component usage that changed between versions.
