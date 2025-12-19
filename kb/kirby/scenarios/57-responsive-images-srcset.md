# Scenario: Responsive images with `$file->srcset()` (incl. WebP/AVIF)

## Goal
Serve appropriately sized images per viewport and device pixel ratio using `srcset` (and optionally modern formats).

## Inputs to ask for
- Target breakpoints/sizes
- Whether art direction is required (different crops per breakpoint)
- Whether WebP/AVIF generation is allowed/desired

## Internal tools/resources to use
- Inspect thumb/srcset config: `kirby://config/thumbs`
- Validate output HTML: `kirby_render_page`

## Implementation steps
1. Use `$image->srcset([...])` to generate `srcset` candidates.
2. Provide a `sizes` attribute that matches the layout.
3. Optional: use `<picture>` with AVIF/WebP sources.

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
  <img
    src="<?= $image->resize(900)->url() ?>"
    srcset="<?= $image->srcset([300, 600, 900, 1200, 1800]) ?>"
    sizes="(max-width: 900px) 100vw, 900px"
    alt=""
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
- Quicktip: Rounded corners: https://getkirby.com/docs/quicktips/rounded-corners
- Quicktip: Purpose of the media folder: https://getkirby.com/docs/quicktips/purpose-of-media-folder
- Reference: `$file->srcset()`: https://getkirby.com/docs/reference/objects/cms/file/srcset
