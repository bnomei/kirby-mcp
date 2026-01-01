# YAML (aliases: “blueprint YAML”, `*.yml`, “YAML handler”)

## Meaning

YAML is the primary format for Kirby **blueprints** and is also used for some structured field values (though many fields can store JSON as well). Kirby’s YAML parsing/encoding behavior can be configured via system options.

## In prompts (what it usually implies)

- “Edit the blueprint YAML” means: modify `site/blueprints/.../*.yml` (but confirm the real blueprints root first).
- “YAML parse error” means: the blueprint or field value is invalid YAML (indentation is a common cause).
- “Blocks/layout field stores YAML/JSON” means: templates convert those values via `toBlocks()` / `toLayouts()`.

## Variants / aliases

- Blueprints: kirby://glossary/blueprint
- Field conversions: kirby://glossary/blocks-field, kirby://glossary/layout-field
- System option: `yaml` (handler config)

## Example

```yaml
title: Article
fields:
  text:
    type: textarea
```

## MCP: Inspect/verify

- Use `kirby_roots` + `kirby_blueprint_read` to inspect resolved blueprint YAML safely (incl. `extends`).
- Use `kirby_read_page_content` to inspect YAML/JSON stored in content fields.
- Check YAML config (runtime install required):
  - `kirby://config/yaml`

## Related terms

- kirby://glossary/blueprint
- kirby://glossary/extends
- kirby://glossary/blocks-field
- kirby://glossary/layout-field

## Links

- https://getkirby.com/docs/glossary#yaml
- https://getkirby.com/docs/reference/system/options/yaml
