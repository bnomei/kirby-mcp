# Blueprint (aliases: “Panel blueprint”, `site/blueprints/*.yml`)

## Meaning

A blueprint is a YAML definition that configures the **Panel UI**: fields, sections, tabs, page/file options, allowed child pages, status workflows, etc.

Blueprints live in the `blueprints` root (often `/site/blueprints`) and can be reused/extended via mixins and `extends`.

## In prompts (what it usually implies)

- “Add a field to the blueprint” means: edit a page/file/user blueprint and add a Panel field definition.
- “Blueprint id” usually means something like `pages/article` (or `files/default`, `users/admin`, …).
- “Why doesn’t the Panel show this field?” often means: blueprint isn’t applied (wrong template/page type) or `extends`/mixins resolve differently than expected.

## Variants / aliases

- Page blueprints: `…/blueprints/pages/<template>.yml`
- Reusable parts (“mixins”):
  - `…/blueprints/fields/*`
  - `…/blueprints/sections/*`
  - `…/blueprints/tabs/*`
- `extends` (see kirby://glossary/extends)
- Panel field types + options: see `kirby://fields` and `kirby://field/{type}`
- Panel section types + options: see `kirby://sections` and `kirby://section/{type}`

## Example

```yaml
title: Article

tabs:
  content:
    sections:
      fields:
        type: fields
        fields:
          headline:
            type: text
          text:
            type: textarea
```

## MCP: Inspect/verify

- Always start with `kirby_roots` to locate the effective `blueprints` root.
- Use `kirby_blueprints_index` to list available blueprint ids.
- Read the resolved blueprint (incl. plugin overrides and `extends`) via:
  - `kirby_blueprint_read`, or
  - resource `kirby://blueprint/{encodedId}` (URL-encode the id, e.g. `pages%2Farticle`)
- When unsure about a Panel field/section option, use:
  - `kirby://field/{type}` and `kirby://section/{type}`

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
