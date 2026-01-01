# Scenario: Basic Panel area

## Goal

Create a new Panel area that renders a custom view component and displays data via props.

## Inputs to ask for

- Plugin id (`vendor/name`)
- Area id (slug), label, icon, menu entry behavior
- Data source (API, JSON file, database)
- Component name and layout expectations

## Internal tools/resources to use

- Resolve project paths: `kirby://roots`
- Confirm plugin is loaded: `kirby_plugins_index`
- Check `panel.dev` if needed: `kirby://config/panel.dev`

## Implementation steps

1. Create `site/plugins/<plugin>/` with `index.php`.
2. Add a `package.json` with `kirbyup` scripts.
3. Register the area in PHP (`areas` + `views`).
4. Register the view component in `src/index.js`.
5. Build a Vue component that renders the view and consumes props.
6. Build or serve the assets with `kirbyup`.

## Examples

### Plugin registration (PHP)

`site/plugins/moviereviews/index.php`

```php
<?php

Kirby::plugin('vendor/moviereviews', [
  'areas' => [
    'moviereviews' => function () {
      return [
        'label' => 'Movie reviews',
        'icon'  => 'video',
        'menu'  => true,
        'link'  => 'moviereviews',
        'views' => [
          [
            'pattern' => 'moviereviews',
            'action'  => function () {
              return [
                'component' => 'moviereviews',
                'title'     => 'Movie reviews',
                'props'     => [
                  'reviews' => [],
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

### Vue entry point

`site/plugins/moviereviews/src/index.js`

```js
import MovieReviews from './components/MovieReviews.vue';

panel.plugin('vendor/moviereviews', {
  components: {
    moviereviews: MovieReviews,
  },
});
```

### View component

`site/plugins/moviereviews/src/components/MovieReviews.vue`

```vue
<template>
  <k-panel-inside>
    <k-view>
      <k-header>Movie reviews</k-header>
      <pre>{{ reviews }}</pre>
    </k-view>
  </k-panel-inside>
</template>

<script>
export default {
  props: {
    reviews: Array,
  },
};
</script>
```

## Verification

- Build or serve assets:
  - `kirbyup serve src/index.js` (K5)
  - `kirbyup build src/index.js --watch` (K6)
- Open the Panel and confirm the area appears in the menu.
- Use `kirby_plugins_index` to confirm the plugin is loaded.

## Glossary quick refs

- kirby://glossary/panel
- kirby://glossary/plugin
- kirby://glossary/route
- kirby://glossary/kirbyuse

## Links

- Panel areas: https://getkirby.com/docs/reference/plugins/extensions/panel-areas
- Cookbook: https://getkirby.com/docs/cookbook/panel/first-panel-area
- UI components: https://getkirby.com/docs/reference/plugins/ui
- Panel tooling: kirby://kb/panel/tooling-kirbyup

## Version notes (K5/K6)

- K5: Vue 2 Panel runtime; use `kirbyup` 3.x and `kirbyup serve` for HMR.
- K6: Vue 3 Panel runtime; use `kirbyup` 4.x and watch-mode builds while HMR is unavailable.
- K5 -> K6: update Vue components to Vue 3-compatible syntax and switch bundler versions.
