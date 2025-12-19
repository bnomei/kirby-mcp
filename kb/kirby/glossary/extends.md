# Extends (aliases: `extends:` in blueprints, “blueprint mixins”)

## Meaning

In Kirby blueprints, `extends` is used to **reuse and compose blueprint parts** (fields, sections, tabs, layouts). This keeps blueprints DRY and makes large Panel setups maintainable.

Because `extends` composition is resolved at runtime, reading the raw YAML file often isn’t enough to know what the Panel actually sees.

## In prompts (what it usually implies)

- “This field/section is coming from another file” usually means: `extends`/mixins.
- “Extends not working” often means: wrong blueprint id/path, wrong mixin location, or a key overwrite/unset.
- “Multiple extends” means: composing more than one mixin into a blueprint.

## Variants / aliases

- Mixins by folder convention (within the blueprints root):
  - `fields/*`, `sections/*`, `tabs/*`
- `extends:` can also be used to override parts of a reused structure

## Example

```yaml
tabs:
  content:
    extends: tabs/seo
```

## MCP: Inspect/verify

- Use `kirby_blueprint_read` (or `kirby://blueprint/{encodedId}`) to inspect the **resolved** blueprint with `extends` applied.
- Use `kirby_blueprints_index` to discover blueprint and mixin ids that exist in the project.
- If you need to confirm a specific Panel field/section type inside a mixin, use `kirby://field/{type}` / `kirby://section/{type}`.

## Related terms

- kirby://glossary/blueprint
- kirby://glossary/panel
- kirby://glossary/query-language

## Links

- https://getkirby.com/docs/guide/blueprints/extending-blueprints
