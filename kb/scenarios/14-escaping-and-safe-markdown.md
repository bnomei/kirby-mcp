# Scenario: Escape output correctly (XSS) + enable safe Markdown

## Goal

Prevent XSS and unsafe markup by applying context-sensitive escaping in templates/snippets and enabling Markdown safe mode for untrusted KirbyText.

## Inputs to ask for

- Which output contexts are present:
  - HTML text vs attributes vs URLs vs JS vs CSS
- Whether KirbyText content can be trusted
- Whether you want global safe mode (`markdown.safe`) or per-render safe mode

## Internal tools/resources to use

- Find templates/snippets quickly: `kirby_templates_index`, `kirby_snippets_index`
- Read config: `kirby://config/markdown` (runtime install required)
- Render and inspect: `kirby_render_page`

## Implementation steps

1. Replace raw output with escaped output using the correct context:
   - `escape()` or `->escape('attr'|'css'|'js'|'url')`
2. If KirbyText is untrusted:
   - render with `kirbytext(['markdown' => ['safe' => true]])`
   - or enable `markdown.safe` globally

## Examples

### Context-sensitive escaping

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<img alt="<?= $image->alt()->escape('attr') ?>" src="<?= $image->url() ?>">
<section style="--columns: <?= $section->columns()->escape('css') ?>">
<script>let v = "<?= $page->jsVariable()->escape('js') ?>";</script>
<iframe src="https://map.example.com/?lat=<?= $map->lat()->escape('url') ?>"></iframe>
```

### Safe Markdown mode for KirbyText

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?= $page->text()->kirbytext(['markdown' => ['safe' => true]]) ?>
```

## Verification

- Render a page and confirm expected markup still appears.
- Try injecting HTML/JS in content fields (in a safe test environment) and confirm it is not executed.

## Glossary quick refs

- kirby://glossary/markdown
- kirby://glossary/kirbytext
- kirby://glossary/template
- kirby://glossary/snippet

## Links

- Guide: Escaping: https://getkirby.com/docs/guide/templates/escaping
- Reference: `markdown.safe` option: https://getkirby.com/docs/reference/system/options/markdown#safe-mode-markdown-safe
