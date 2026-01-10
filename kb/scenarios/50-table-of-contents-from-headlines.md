# Scenario: Generate a table of contents (ToC) from headlines

## Goal

Generate a table of contents based on headings in:

- KirbyText fields (textarea → `kt()`)
- Blocks fields (extra pattern)

## Inputs to ask for

- Which heading levels to include (`h2`, `h3`, …)
- Whether editors should insert a `(toc)` placeholder or templates always render a ToC
- Whether content is KirbyText, Blocks, or both
- Whether content uses the Editor field (KirbyText hooks won't apply)

## Internal tools/resources to use

- Inspect hooks/extensions: `kirby://hooks`, `kirby://extensions`
- Inventory snippets/plugins: `kirby_snippets_index`, `kirby_plugins_index`
- Validate rendering: `kirby_render_page`

## Implementation steps

1. Create a plugin that:
   - anchors headlines in output (hook or field method)
   - generates a headline collection for the ToC
   - for Blocks: add `id` to heading block snippet and collect headings via `toBlocks()`
2. Add a `toc` snippet that renders the headline collection.
3. Optionally support a `(toc)` placeholder via `kirbytext:after`.

## Examples (cookbook patterns)

### Add anchored headlines (hook approach)

`site/plugins/toc/index.php` (excerpt)

```php
Kirby::plugin('acme/toc', [
  'hooks' => [
    'kirbytext:after' => [
      function (string $text) {
        $levels = option('acme.toc.headlines', 'h2|h3');
        $pattern = is_array($levels) ? implode('|', $levels) : $levels;

        return preg_replace_callback('!<(' . $pattern . ')>(.*?)</\\1>!s', function ($match) {
          $id = Str::slug(Str::unhtml($match[2]));
          return '<' . $match[1] . ' id="' . $id . '"><a href="#' . $id . '">' . $match[2] . '</a></' . $match[1] . '>';
        }, $text);
      },
    ],
  ],
]);
```

### Anchor headlines (field method approach)

If you register `anchorHeadlines` as a field method, you can do:

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?= $page->text()->kt()->anchorHeadlines(['h2', 'h3']) ?>
```

### Placeholder pattern `(toc)` (conceptual)

- use a `kirbytext:after` hook to replace `(toc)` with `snippet('toc', ...)`

## Verification

- Add headings to a page and confirm:
  - headings become anchored (`id=...`)
  - ToC links jump to the correct sections

## Glossary quick refs

- kirby://glossary/content
- kirby://glossary/plugin
- kirby://glossary/hook
- kirby://glossary/field

## Links

- Cookbook: Table of contents: https://getkirby.com/docs/cookbook/navigation/table-of-contents
- Guide: Plugins: https://getkirby.com/docs/guide/plugins
