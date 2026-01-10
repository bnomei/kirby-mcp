# Scenario: Responsive images with `$file->srcset()` (incl. WebP/AVIF)

## Goal

Serve appropriately sized images per viewport and device pixel ratio using `srcset` (and optionally modern formats).

## Inputs to ask for

- Target breakpoints/sizes
- Whether art direction is required (different crops per breakpoint)
- Whether WebP/AVIF generation is allowed/desired
- Whether the project overrides `file::url` or media handling (serving from `/content`)

## Internal tools/resources to use

- Inspect thumb/srcset config: `kirby://config/thumbs`
- Validate output HTML: `kirby_render_page`

## Implementation steps

1. Optional: define `thumbs.srcsets` presets in `config.php` to reuse sizes.
2. Use `$image->srcset([...])` (or `$image->srcset('preset')`) to generate `srcset` candidates.
3. Provide a `sizes` attribute that matches the layout.
4. Optional: use `<picture>` with AVIF/WebP sources or media queries for art direction.

## Examples (cookbook pattern)

```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<?php if ($image = $page->image('flower-power.jpg')): ?>
  <?php $thumb = $image->resize(900) ?>
  <img
    src="<?= $thumb->url() ?>"
    srcset="<?= $image->srcset([300, 600, 900, 1200, 1800]) ?>"
    sizes="(max-width: 900px) 100vw, 900px"
    width="<?= $thumb->width() ?>"
    height="<?= $thumb->height() ?>"
    alt="<?= $image->alt() ?>"
  >
<?php endif ?>
```

## Verification

- Confirm multiple variants are generated in `media/`.
- Check that browser selects appropriate size (devtools network).

## Glossary quick refs

- kirby://glossary/media
- kirby://glossary/thumb

## Links

- Cookbook: Responsive images: https://getkirby.com/docs/cookbook/performance/responsive-images
- Quicktip: Art-directed blog posts: https://getkirby.com/docs/quicktips/art-directed-blog-posts
- Quicktip: Purpose of the media folder: https://getkirby.com/docs/quicktips/purpose-of-media-folder
- Reference: `$file->srcset()`: https://getkirby.com/docs/reference/objects/cms/file/srcset
