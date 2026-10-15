# Blueprint (aliases: “Panel blueprint”, `site/blueprints/*.yml`)

## Meaning

A blueprint is a YAML definition that configures the Panel UI: value/display fields, tabs, columns, model options and status workflows. Kirby 6 replaces sections with fields and removes presets. Built-in legacy sections auto-convert; custom sections must be ported.

Blueprints live in the `blueprints` root (often `/site/blueprints`) and can be reused/extended via mixins and `extends`.

## In prompts (what it usually implies)

- “Add a field to the blueprint” means: edit a page/file/user blueprint and add a Panel field definition.
- “Blueprint id” usually means something like `pages/article` (or `files/default`, `users/admin`, …).
- “Why doesn’t the Panel show this field?” often means: blueprint isn’t applied (wrong template/page type) or `extends`/mixins resolve differently than expected.

## Variants / aliases

- Page blueprints: `…/blueprints/pages/<template>.yml`
- Reusable parts (“mixins”):
  - `…/blueprints/fields/*`
  - display lists also use field blueprints (`pagelist`/`filelist`)
  - `…/blueprints/tabs/*`
- `extends` (see kirby://glossary/extends)
- Panel field types + options: see `kirby://fields` and `kirby://field/{type}`
- Legacy section catalog: `kirby://sections`; use the replacement field for new blueprints.

## Example

```yaml
title: Article

tabs:
  content:
    fields:
      headline:
        type: text
      text:
        type: textarea
```

Define shared fields at the root and reference them with YAML lists (`fields: [headline, text]`) in tabs/columns. A mapping such as `headline: true` is not a root-field reference. Names must be unique case-insensitively across the blueprint. In PHP, use `Kirby\Blueprint\Blueprint`; tabs are objects, not arrays.

## MCP: Inspect/verify

- Always start with `kirby_roots` to locate the effective `blueprints` root.
- Use `kirby_blueprints_index` to list available blueprint ids.
- Read raw blueprint data (including plugin sources and `extends`, but not model field normalization) via:
  - `kirby_blueprint_read`, or
  - resource `kirby://blueprint/{encodedId}` (URL-encode the id, e.g. `pages%2Farticle`)
- Use model `blueprint()->fields()` for normalized fields; these include non-value display fields. Check schema `hasValue` before planning writes.
- When unsure about a Panel field option, use `kirby://field/{type}`.

## Related terms

- kirby://glossary/panel
- kirby://glossary/template
- kirby://glossary/field
- kirby://glossary/extends
- kirby://glossary/query-language

## Links

- https://getkirby.com/docs/guide/blueprints/introduction
- https://getkirby.com/docs/guide/blueprints/extending-blueprints
- https://getkirby.com/docs/guide/blueprints/query-language
- https://getkirby.com/docs/reference/panel/fields
- https://getkirby.com/docs/reference/panel/sections
- https://getkirby.com/docs/reference/panel/blueprints
