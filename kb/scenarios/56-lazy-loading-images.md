# Scenario: Lazy-load images (HTML + Kirby thumbs)

## Goal

Improve perceived performance by lazy-loading below-the-fold images (and optionally iframes).

## Inputs to ask for

- Which templates/components render large image lists (blog, gallery, search results)
- Which images must stay eager (hero/LCP) vs lazy (below-the-fold)
- Whether you need a placeholder (blur-up/LQIP) or just native lazy loading
- Whether the project already uses responsive images (`srcset`) and/or JS lazy-loading

## Internal tools/resources to use

- Inventory templates/snippets: `kirby_templates_index`, `kirby_snippets_index`
- Validate output: `kirby_render_page`

## Implementation steps

1. Add native lazy-loading attributes:
   - `loading="lazy"` on `<img>`
   - `decoding="async"` as a complement
2. Keep above-the-fold images eager (`loading="eager"` or omit `loading`).
3. Ensure image sizes are constrained (responsive images help).
4. Include width/height (or CSS `aspect-ratio`) to prevent layout shift.
5. Optionally generate thumbnails via `thumb()`/`srcset()` to avoid huge originals.

## Examples

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php if ($image = $page->image()): ?>
  <?php $thumb = $image->resize(600) ?>
  <img
    src="<?= $thumb->url() ?>"
    srcset="<?= $image->srcset([300, 600, 900, 1200]) ?>"
    sizes="(max-width: 600px) 100vw, 600px"
    width="<?= $thumb->width() ?>"
    height="<?= $thumb->height() ?>"
    loading="lazy"
    decoding="async"
    alt="<?= $image->alt() ?>"
  >
<?php endif ?>
```

## Verification

- Confirm images still appear when scrolling.
- Check generated image sizes in `media/` and ensure originals aren’t shipped accidentally.

## Glossary quick refs

- kirby://glossary/template
- kirby://glossary/snippet
- kirby://glossary/thumb

## Links

- Cookbook: Lazy loading: https://getkirby.com/docs/cookbook/performance/lazy-loading
- Cookbook: Responsive images: https://getkirby.com/docs/cookbook/performance/responsive-images
