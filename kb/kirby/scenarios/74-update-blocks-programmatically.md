# Scenario: Update a `blocks` field programmatically (create + append blocks)

## Goal

Modify a `blocks` field in code, e.g.:

- append blocks based on a frontend form
- migrate/transform existing blocks

## Inputs to ask for

- Blocks field name (e.g. `text`)
- Which block types are involved (`heading`, `text`, `quote`, custom)
- Whether updates should preserve existing blocks
- Whether updates happen in a migration or via user-triggered actions

## Internal tools/resources to use

- Confirm blocks field exists in blueprint: `kirby://blueprint/{encodedId}`
- Read current value: `kirby://page/content/{encodedIdOrUuid}`
- Use safe writes: prefer `kirby_update_page_content` for controlled updates (requires confirm)
- Reference storage/payload guidance: `kirby://field/blocks/update-schema`

## Implementation steps

1. Convert field value to blocks: `$page->text()->toBlocks()`.
2. Create new `Block` instances with required `content`.
3. Append via `$blocks->add(new Blocks([...]))`.
4. Authenticate/impersonate before updating.
5. Update field with `json_encode($blocks->toArray())`.

## Examples (quicktip; abridged)

```php
$blocks = $page->text()->toBlocks();

$heading = new Kirby\Cms\Block([
  'type' => 'heading',
  'content' => ['text' => 'This is a heading', 'level' => 'h2'],
]);

$blocks = $blocks->add(new Kirby\Cms\Blocks([$heading]));

$kirby->impersonate('kirby');
$page->update([
  'text' => json_encode($blocks->toArray()),
]);
```

## Verification

- Confirm the `blocks` JSON stays valid and renders in the frontend and Panel.

## Glossary quick refs

- kirby://glossary/blocks
- kirby://glossary/block
- kirby://glossary/field
- kirby://glossary/blueprint

## Links

- Quicktip: Add blocks programmatically: https://getkirby.com/docs/quicktips/update-blocks-programmatically
- Reference: Blocks field: https://getkirby.com/docs/reference/panel/fields/blocks
- Reference: Blocks extension: https://getkirby.com/docs/reference/plugins/extensions/blocks
