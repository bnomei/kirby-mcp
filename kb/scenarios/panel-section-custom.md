# Scenario: Custom Panel section

## Goal

Create a custom Panel section that loads props from PHP and renders a Vue component.

## Inputs to ask for

- Plugin id (`vendor/name`)
- Section type name (slug) and label
- Data fields to expose to the section (props)
- Blueprint(s) where the section is used

## Internal tools/resources to use

- Resolve project paths: `kirby://roots`
- Confirm plugin is loaded: `kirby_plugins_index`
- Inspect blueprints: `kirby_blueprints_index` + `kirby_blueprint_read`

## Implementation steps

1. Register the section in `index.php` and move the props definition into a separate file.
2. Register the section component in `src/index.js`.
3. Implement the Vue component with `data()` and `created()` to call `this.load()`.
4. Use the section in a blueprint.
5. Build or serve assets with `kirbyup`.

## Examples

### Plugin registration (PHP)

`site/plugins/linksection/index.php`

```php
<?php

Kirby::plugin('vendor/linksection', [
  'sections' => [
    'links' => require __DIR__ . '/sections/links.php',
  ],
]);
```

`site/plugins/linksection/sections/links.php`

```php
<?php

return [
  'props' => [
    'label' => function ($label = 'Links') {
      return $label;
    },
    'layout' => function ($layout = 'list') {
      return $layout;
    },
    'links' => function ($links = []) {
      return $links;
    },
  ],
];
```

### Vue entry point

`site/plugins/linksection/src/index.js`

```js
import Links from './components/sections/Links.vue';

panel.plugin('vendor/linksection', {
  sections: {
    links: Links,
  },
});
```

### Section component

`site/plugins/linksection/src/components/sections/Links.vue`

```vue
<template>
  <section class="k-links-section">
    <header class="k-section-header">
      <h2 class="k-headline">{{ label }}</h2>
    </header>
    <k-collection :items="links" :layout="layout" />
  </section>
</template>

<script>
export default {
  data() {
    return {
      label: '',
      layout: 'list',
      links: [],
    };
  },
  async created() {
    const response = await this.load();
    this.label = response.label;
    this.layout = response.layout;
    this.links = response.links;
  },
};
</script>
```

### Blueprint usage

`site/blueprints/site.yml`

```yaml
sections:
  links:
    type: links
    label: Getting started
```

## Verification

- Build or serve assets with `kirbyup`.
- Open the Panel and confirm the section renders.
- Update blueprint props and verify the UI updates.

## Glossary quick refs

- kirby://glossary/panel
- kirby://glossary/blueprint
- kirby://glossary/section
- kirby://glossary/kirbyuse

## Links

- Cookbook: https://getkirby.com/docs/cookbook/panel/first-panel-section
- Panel sections: https://getkirby.com/docs/reference/plugins/extensions/sections
- Panel tooling: kirby://kb/panel/tooling-kirbyup
- Panel composables: kirby://kb/panel/tooling-kirbyuse

## Version notes (K5/K6)

- K5: Vue 2 Panel runtime; `kirbyup` 3.x targets Vue 2.7.
- K6: Vue 3 Panel runtime; `kirbyup` 4.x targets Vue 3, and `kirbyuse` 2.x requires an import map entry.
- K5 -> K6: update Vue component syntax if needed and switch bundler versions.
