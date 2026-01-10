# Panel pattern - field options with SVG previews

## Goal

Build a custom field that renders selectable options with SVG previews (icons or symbols) while keeping option data computed on the server.

## Inputs to ask for

- Source of SVGs (folder of files or sprite sheet)
- Include/exclude lists and naming rules
- Single vs multi-select behavior
- Search and display limits for large icon sets

## MCP tools/resources to use

- kirby://roots
- kirby_plugins_index
- kirby_blueprints_index
- kirby_blueprint_read
- kirby://extension/fields

## Files to touch

- site/plugins/<plugin>/index.php
- site/plugins/<plugin>/index.js or site/plugins/<plugin>/src/index.js
- site/plugins/<plugin>/src/components/<FieldParts>.vue
- site/plugins/<plugin>/index.css

## Implementation steps

1. Extend an existing field (for example tags or multiselect) and compute options server-side.
2. Build option objects with text, value, and an SVG string or sprite reference.
3. Expose options via field `props`/`computed` so the API payload stays consistent.
4. Cache computed option lists and sanitize SVG markup before returning to the Panel.
5. Extend the field input components to render SVGs in tags and dropdown choices.
6. Prefer kirbyup and kirbyuse for new work; if shipping prebuilt JS, keep extensions minimal and compatible with core components.

## Examples

- Icons loaded from an assets folder and displayed inside a tags input.
- Icons loaded from an SVG sprite and referenced via `<use>` with a generated URL.
- Options filtered by include/exclude lists to reduce the dropdown size.

## Panel JS (K5)

```js
// site/plugins/example-icon-field/src/index.js
const IconField = {
  extends: 'k-multiselect-field',
  computed: {
    optionsWithSvg() {
      return (this.options || []).map((option) => ({
        text: option.text,
        value: option.value,
        svg: option.svg || '',
      }));
    },
    dropdownOptions() {
      return this.optionsWithSvg.map((option) => ({
        text: option.text,
        value: option.value,
        svg: option.svg,
        click: () => this.select(option.value),
      }));
    },
  },
  methods: {
    svgFor(value) {
      const match = this.optionsWithSvg.find((option) => option.value === value);
      return match ? match.svg : '';
    },
    removeTag(tag) {
      this.$emit(
        'input',
        this.value.filter((value) => value !== tag),
      );
    },
    select(value) {
      const values = Array.isArray(this.value) ? [...this.value] : [];
      if (!values.includes(value)) values.push(value);
      this.$emit('input', values);
    },
  },
  template: `
    <k-field v-bind="$props" class="k-icon-field">
      <div class="k-icon-field-tags">
        <k-tag
          v-for="tag in value"
          :key="tag"
          :removable="true"
          @remove="removeTag(tag)"
        >
          <span class="k-icon-field-svg" v-html="svgFor(tag)"></span>
          <span class="k-icon-field-text">{{ tag }}</span>
        </k-tag>
      </div>
      <k-dropdown-content :options="dropdownOptions" />
    </k-field>
  `,
};

panel.plugin('example/icon-field', {
  fields: {
    icon: IconField,
  },
});
```

## Verification

- Options render with SVG previews in the dropdown and in selected tags.
- Search and pagination behave correctly with large icon sets.
- SVG content is sanitized and cached.

## Gotchas

- Inline SVGs can bloat API responses; prefer sprite references when the list is large.
- Never accept user-supplied SVG without sanitizing; `v-html` will render raw markup.

## Links

- https://getkirby.com/docs/reference/plugins/extensions/fields
- https://getkirby.com/docs/reference/plugins/ui

## Version notes (K5/K6)

- K5: Vue 2 field component extension patterns.
- K6: Vue 3 field components; verify custom input extensions still render.
- K5 -> K6: rebuild the field bundle for the Vue 3 runtime.
