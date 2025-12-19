# Structure field (aliases: `structure`, `toStructure()`, “structured field content”)

## Meaning

The structure field is a Panel field type for editing **repeatable sets of sub-fields** (think “rows” with columns like title/date/image). In content files, structure values are stored as YAML, which you then parse in templates with `$field->yaml()` (array) or `$field->toStructure()` (Kirby collection with chainable field access).

Structure is a common “bridge” between Panel UI and code because editors can manage lists, while developers can render them predictably.

## In prompts (what it usually implies)

- “Add a structure field for X” means: define it in a page blueprint and add nested `fields`.
- “Loop over structure items” means: `$page->myfield()->toStructure()` and then `$item->subfield()`.
- “Nested structure” means: call `toStructure()` on nested fields as well.

## Variants / aliases

- Blueprint field type: `type: structure`
- Parsing methods:
  - `$field->yaml()` → PHP array
  - `$field->toStructure()` → `Kirby\Cms\Structure`
- Common related converters inside structure items:
  - `$item->images()->toFiles()` (when the subfield stores file ids)
  - `$item->pages()->toPages()` (when the subfield stores page ids)

## Example

Blueprint snippet:

```yaml
fields:
  links:
    type: structure
    fields:
      label:
        type: text
      url:
        type: url
```

Template usage:

```php
<?php foreach ($page->links()->toStructure() as $link): ?>
  <a href="<?= $link->url()->escape() ?>"><?= $link->label()->escape() ?></a>
<?php endforeach ?>
```

## MCP: Inspect/verify

- Use `kirby_roots` to locate `blueprints` and `content` roots.
- Find the defining blueprint with `kirby_blueprints_index`, then read it via `kirby://blueprint/<encodedId>`.
- Inspect real stored values with `kirby_read_page_content` (or `kirby://page/content/{encodedIdOrUuid}`) and locate the structure field’s raw YAML.
- Confirm parsing behavior via `kirby_eval`:
  - example: `return page('home')->links()->toStructure()->toArray();`

## Related terms

- kirby://glossary/field
- kirby://glossary/field-method
- kirby://glossary/yaml
- kirby://glossary/blueprint

## Links

- https://getkirby.com/docs/reference/panel/fields/structure
- https://getkirby.com/docs/reference/templates/field-methods/to-structure
- https://getkirby.com/docs/cookbook/content-structure/structured-field-content

