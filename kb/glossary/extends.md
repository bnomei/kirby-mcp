# Extends (aliases: `extends:` in blueprints, “blueprint mixins”)

## Meaning

In Kirby blueprints, `extends` is used to **reuse and compose blueprint parts** (fields, tabs, layouts). This keeps blueprints DRY and makes large Panel setups maintainable. Kirby 5 legacy section mixins should become field mixins: built-in sections auto-convert, but custom sections need `DisplayField` ports. See `kirby://kb/scenarios/06-blueprints-reuse-extends`.

Because `extends` composition is resolved at runtime, reading the raw YAML file often isn’t enough to know what the Panel actually sees.

## In prompts (what it usually implies)

- “This field/section is coming from another file” usually means: `extends`/mixins.
- “Extends not working” often means: wrong blueprint id/path, wrong mixin location, or a key overwrite/unset.
- “Multiple extends” means: composing more than one mixin into a blueprint.

## Variants / aliases

- Mixins by folder convention (within the blueprints root):
  - `fields/*`, `tabs/*`; retained legacy `sections/*` paths need content migration, not just renaming
- `extends:` can also be used to override parts of a reused structure

## Example

```yaml
tabs:
  content:
    extends: tabs/seo
```

## MCP: Inspect/verify

- Use `kirby_blueprint_read` (or `kirby://blueprint/{encodedId}`) to inspect loaded blueprint data. `Blueprint::load()` is not model normalization: use model `blueprint()->fields()`/content schemas to see normalized fields and root references.
- Use `kirby_blueprints_index` to discover blueprint and mixin ids that exist in the project.
- Confirm a Panel field type inside a mixin with `kirby://field/{type}`; section resources are legacy migration references.

## Related terms

- kirby://glossary/blueprint
- kirby://glossary/panel
- kirby://glossary/query-language

## Links

- https://getkirby.com/docs/guide/blueprints/extending-blueprints
