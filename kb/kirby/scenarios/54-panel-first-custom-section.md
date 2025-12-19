# Scenario: Create a custom Panel section (plugin + Vue section)

## Goal
Add a new section type for blueprints (Panel layout), e.g. to show computed data, dashboards, or custom listings.

## Inputs to ask for
- Section type name and props
- What data the section displays (Kirby content, external API, computed stats)
- Whether the section needs backend endpoints

## Internal tools/resources to use
- Panel section reference: `kirby://sections` + `kirby://section/{type}`
- Inventory plugins: `kirby_plugins_index`
- Confirm roots: `kirby://roots`

## Implementation steps
1. Register the section type in PHP (`sections` extension).
2. Register the Vue section component in `src/index.js`.
3. Implement the Vue section component (usually uses `k-section`, `k-items`, etc.).
4. Add the section to a blueprint and test.

## Examples
```php
Kirby::plugin('acme/stats-section', [
  'sections' => [
    'stats' => [
      'props' => [
        'totalPages' => fn () => site()->index()->count(),
      ],
    ],
  ],
]);
```

## Verification
- Open the Panel page that uses the blueprint and confirm the section renders.
- Confirm the section respects permissions and doesn’t leak data.

## Glossary quick refs

- kirby://glossary/panel
- kirby://glossary/section
- kirby://glossary/plugin
- kirby://glossary/blueprint

## Links
- Cookbook: First Panel section: https://getkirby.com/docs/cookbook/panel/first-panel-section
- Reference: Sections extension: https://getkirby.com/docs/reference/plugins/extensions/sections
- Guide: Panel plugin setup: https://getkirby.com/docs/guide/plugins/plugin-setup-panel
