# Scenario: Create a first custom Panel area (plugin + Vue view)

## Goal

Add a custom Panel “area” (new sidebar menu entry) that renders a custom view, typically powered by:

- PHP (area + views + props)
- Vue components (Panel UI)

## Inputs to ask for

- Plugin id (vendor/name) and area slug
- Area label, icon, and permission requirements
- Data source (Kirby content vs third-party API)
- Whether a build step is available for Panel assets (kirbyup bundler)

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (plugin location)
- Inventory plugins: `kirby_plugins_index`
- Reference: Panel areas extension docs: `kirby://extension/panel-areas` (if available) or use official links below

## Implementation steps

1. Create plugin folder:
   - `site/plugins/<plugin>/`
2. Add `index.php` with `Kirby::plugin(..., ['areas' => ...])`.
   - Panel `pattern` values must not start with `panel/`
3. Create `src/index.js` that registers the Vue component used by the view.
4. Add the Vue component (e.g. `src/components/<View>.vue`).
5. Build Panel assets into `site/plugins/<plugin>/index.js` (via kirbyup or your bundler).

## Examples (from the cookbook recipe; abridged)

### PHP: define an area and return a view with props

`site/plugins/moviereviews/index.php` (excerpt)

```php
Kirby::plugin('cookbook/moviereviews', [
  'areas' => [
    'moviereviews' => function () {
      return [
        'label' => 'NYT Movie reviews',
        'icon'  => 'video',
        'menu'  => true,
        'link'  => 'moviereviews',
        'views' => [
          [
            'pattern' => 'moviereviews',
            'action'  => function () {
              return [
                'component' => 'moviereviews',
                'title'     => 'NYT Movie reviews',
                'props'     => [
                  'layout' => 'cards',
                ],
              ];
            }
          ]
        ]
      ];
    }
  ],
]);
```

### JS: register the view component

`site/plugins/moviereviews/src/index.js`

```js
import MovieReviews from './components/MovieReviews.vue';

panel.plugin('cookbook/moviereviews', {
  components: {
    moviereviews: MovieReviews,
  },
});
```

## Verification

- Open the Panel and confirm the new area appears in the sidebar.
- Navigate to the area route and confirm the view renders without console errors.

## Glossary quick refs

- kirby://glossary/panel
- kirby://glossary/kirbyuse
- kirby://glossary/plugin
- kirby://glossary/component
- kirby://glossary/asset

## Links

- Cookbook: First Panel area: https://getkirby.com/docs/cookbook/panel/first-panel-area
- Cookbook: Advanced Panel area: https://getkirby.com/docs/cookbook/panel/advanced-panel-area
- Reference: Panel areas: https://getkirby.com/docs/reference/plugins/extensions/panel-areas
- Guide: Panel plugin setup: https://getkirby.com/docs/guide/plugins/plugin-setup-panel
