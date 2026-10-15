# Scenario: Bundle or not (Panel plugins)

## Goal

Decide whether a Panel plugin needs a build step and how to convert a single-file component to plain JS if you want to avoid bundling.

## Inputs to ask for

- Are you using Vue SFCs, TypeScript, PostCSS, or modern JS?
- Do you need HMR or a dev server?
- Can you ship a simple `index.js` + `index.css`?

## Internal tools/resources to use

- Resolve project paths: `kirby://roots`
- Confirm plugin is loaded: `kirby_plugins_index`
- Check `panel.dev` if needed: `kirby://config/panel.dev`

## Implementation steps

1. If the plugin is simple, consider shipping plain JS and CSS with no bundler.
2. If you need SFCs or modern tooling, use `kirbyup` as the default bundler.
3. To convert an SFC to plain JS:
   - move the `<template>` into a template string in `index.js`
   - move `<script>` logic into the component definition
   - move `<style>` into `index.css`
4. Re-test the plugin in the Panel and verify events/props still work.

## Examples

### Plain JS component (no bundler)

`site/plugins/audio-block/index.js`

```js
panel.plugin('vendor/audio-block', {
  blocks: {
    audio: {
      template: `
        <k-block-figure
          :is-empty="!content.url"
          empty-icon="audio-file"
          empty-text="No file selected yet"
          @open="open"
          @update="update"
        />
      `,
      props: {
        content: Object,
      },
      emits: ['open', 'update'],
      methods: {
        open() {
          this.$emit('open');
        },
        update(data) {
          this.$emit('update', data);
        },
      },
    },
  },
});
```

### CSS without bundling

`site/plugins/audio-block/index.css`

```css
.k-audio-block {
  padding: 0.5rem;
}
```

## Verification

- Load the Panel and confirm the component renders.
- Edit content and ensure events still update values.
- If bundling, confirm `index.js` and `index.css` are updated on build.

## Glossary quick refs

- kirby://glossary/panel
- kirby://glossary/kirbyuse
- kirby://glossary/plugin

## Links

- Cookbook: https://getkirby.com/docs/cookbook/plugins/to-bundle-or-not-to-bundle
- kirbyup: kirby://kb/panel/tooling-kirbyup

## Kirby 6 migration

Use Vue 3 and kirbyup 4 (`dev` supports HMR). Rebuild Kirby 5 legacy Vue 2 bundles. Simple ESM plugins can remain unbundled; import Vue from `vue`. If using kirbyuse, bundle it: beta.1 provides no plugin import-map hook. See `kirby://kb/panel/tooling-kirbyuse`.
