---
name: kirby-panel-and-blueprints
description: Designs Kirby 6 blueprints and Panel UI, including reuse/extends, programmable blueprints, value/display fields and areas. Use when changing Panel schemas or migrating legacy sections.
---

# Kirby Panel and Blueprints

Use Kirby 6 conventions for new work. For existing blueprints, consult `kirby://kb/scenarios/83-migrate-kirby-5-to-6` when migration is needed.

## Kirby 6 rules

- Use `pagepicker`/`filepicker`/`userpicker` for stored selections; use non-value `pagelist`/`filelist` for lists. Built-in legacy sections auto-convert; custom sections require `DisplayField` class ports.
- Remove presets. Define fields at the root and place them via list references (`fields: [intro, related]`) in tabs/columns, never `related: true`. Names collide case-insensitively.
- Nest option query/API configuration inside `options`; invalid options now remain stored. Quote YAML dates and reserved scalars; use `true`/`false` booleans.
- PHP fields are classes; Vue 3 uses explicit `value`/`input` and `emits`. Build with kirbyup 4; follow the Panel migration guide for trusted HTML and backend controllers.

## KB entry points

- `kirby://kb/panel/reference-editor`
- `kirby://kb/panel/reference-runtime`
- `kirby://kb/scenarios/06-blueprints-reuse-extends`
- `kirby://kb/scenarios/19-programmable-blueprints`
- `kirby://kb/scenarios/53-panel-first-custom-field`
- `kirby://kb/scenarios/54-panel-first-custom-section`
- `kirby://kb/panel/reference-fields`
- `kirby://kb/scenarios/panel-bundling-decisions`

## Required inputs

- Content model and required fields.
- Panel UX (tabs/columns/value and display fields) and validation rules.
- Whether to extend an existing blueprint.

## Minimal blueprint skeleton

```yaml
title: Example
status:
  draft: true
  listed: true
fields:
  title:
    type: text
  text:
    type: textarea
```

## Extends example

```yaml
extends: pages/default
```

## Common pitfalls

- Duplicating fields instead of using `extends`.
- Implementing Panel UI logic in templates instead of blueprints or plugins.

## Workflow

1. Clarify the content model, required fields, and Panel UX expectations.
2. Call `kirby:kirby_init` and read `kirby://roots`.
3. Inspect existing blueprints and patterns:
   - `kirby:kirby_blueprints_index`
   - `kirby:kirby_blueprint_read`
4. Use Panel reference resources for field choices:
   - `kirby://fields`
   - `kirby://sections` for existing section configuration and migration guidance
5. Check plugin surface when custom Panel UI is needed:
   - `kirby:kirby_plugins_index`
   - `kirby://extensions`
6. Search the KB with `kirby:kirby_search` (examples: "blueprints reuse extends", "programmable blueprints", "custom panel field", "custom panel section", "panel branding").
7. Implement convention-aligned YAML/PHP; prefer `extends` and shared field definitions over duplication.
8. Re-read raw blueprint configuration with `kirby:kirby_blueprint_read`, inspect normalized model fields separately and open the Panel to verify layout/save behavior. Render frontend output when relevant; rendering alone does not test Panel UI.
