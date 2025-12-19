# Scenario: Create custom Blocks (incl. nested blocks) via a plugin

## Goal

Add custom block types for the `blocks` field, including nested blocks (block contains another blocks field).

## Inputs to ask for

- Block type names (must be unique; avoid core block names like `text`, `heading`, …)
- Fields for each block type
- Whether you need Panel preview customization (JS/CSS) or default preview is fine

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Inspect plugins/snippets/blueprints: `kirby_plugins_index`, `kirby_snippets_index`, `kirby_blueprints_index`
- Panel field reference: `kirby://field/blocks`
- Render and inspect frontend output: `kirby_render_page`

## Implementation steps

1. Create a plugin folder, e.g. `site/plugins/faq-block/`.
2. Add block blueprints:
   - `site/plugins/<plugin>/blueprints/blocks/<block>.yml`
3. Add output snippets:
   - `site/plugins/<plugin>/snippets/blocks/<block>.php`
4. Register blueprint + snippet paths in `index.php` via `Kirby::plugin(..., [...])`.
5. Optional: add `index.js` (Panel preview templates) and `index.css` (preview styles).

## Examples (FAQ block with nested Q/A blocks)

### Block blueprints

`site/plugins/faq-block/blueprints/blocks/faq.yml`

```yaml
name: FAQ
icon: star
fields:
  headline:
    type: text
  text:
    type: writer
  blocks:
    type: blocks
    fieldsets:
      - faqItem
```

`site/plugins/faq-block/blueprints/blocks/faqItem.yml`

```yaml
name: Question / Answer
icon: box
fields:
  question:
    type: text
  answer:
    type: writer
```

### Output snippets

`site/plugins/faq-block/snippets/blocks/faq.php`

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 * @var Kirby\Cms\Block $block
 */
?>

<div class="faq">
  <h2><?= $block->headline() ?></h2>
  <div><?= $block->text() ?></div>
  <dl><?= $block->blocks()->toBlocks() ?></dl>
</div>
```

`site/plugins/faq-block/snippets/blocks/faqItem.php`

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 * @var Kirby\Cms\Block $block
 */
?>

<dt><?= $block->question() ?></dt>
<dd><?= $block->answer() ?></dd>
```

### Plugin registration

`site/plugins/faq-block/index.php`

```php
<?php

use Kirby\Cms\App as Kirby;

Kirby::plugin('your-project/faq-block', [
  'blueprints' => [
    'blocks/faq'     => __DIR__ . '/blueprints/blocks/faq.yml',
    'blocks/faqItem' => __DIR__ . '/blueprints/blocks/faqItem.yml',
  ],
  'snippets' => [
    'blocks/faq'     => __DIR__ . '/snippets/blocks/faq.php',
    'blocks/faqItem' => __DIR__ . '/snippets/blocks/faqItem.php',
  ],
]);
```

## Verification

- Add a blocks field to a page blueprint and include your custom block types in `fieldsets`.
- Create blocks in the Panel and confirm both Panel preview and frontend output are correct.

## Glossary quick refs

- kirby://glossary/blocks
- kirby://glossary/block
- kirby://glossary/plugin
- kirby://glossary/blueprint

## Links

- Cookbook: Nested blocks: https://getkirby.com/docs/cookbook/content-structure/nested-blocks
- Reference: Blocks field: https://getkirby.com/docs/reference/panel/fields/blocks
