# Scenario: Dynamic Open Graph images via `.png` content representation

## Goal
Generate Open Graph (`og:image`) images dynamically per page using a `.png` content representation like:
- `article.png` for `article` pages

## Inputs to ask for
- Which template(s) should get dynamic OG images
- Desired dimensions (Open Graph default: `1200×628`)
- Text and branding elements to render (title, logo, colors)
- Available PHP extensions on the server (GD is required for the cookbook approach)

## Internal tools/resources to use
- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Ensure the base template exists (content representations need a base template):
  - `kirby_templates_index`
- Validate the HTML head output: `kirby_render_page`

## Implementation steps
1. Add a `.png` representation template:
   - `site/templates/<template>.png.php` (e.g. `article.png.php`)
2. Generate an image using GD (canvas, colors, text, optional logo).
3. Update your HTML head to point `og:image` to the `.png` representation URL.
4. Add a fallback `og:image` for templates that don’t have a `.png` representation.

## Examples (cookbook pattern)

### `og:image` head markup
```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */
?>

<meta property="og:image:type" content="image/png">
<meta property="og:image" content="<?= e(
  $page->template()->name() === 'article',
  $page->url() . '.png',
  'https://yourdomain.tld/opengraph.png'
) ?>">
```

### Minimal `.png` representation (sketch)
`site/templates/article.png.php`
```php
<?php
/**
 * @var Kirby\Cms\App $kirby
 * @var Kirby\Cms\Site $site
 * @var Kirby\Cms\Page $page
 */

$canvas = imagecreatetruecolor(1200, 628);
$backgroundColor = imagecolorallocate($canvas, 255, 255, 255);
$textColor = imagecolorallocate($canvas, 66, 66, 66);

imagefill($canvas, 0, 0, $backgroundColor);

$fontFile = './assets/fonts/arial.ttf';
$title = wordwrap($page->title()->toString(), 30);
imagefttext($canvas, 50, 0, 150, 185, $textColor, $fontFile, $title);

imagepng($canvas);
imagedestroy($canvas);
```

## Verification
- Open the `.png` URL directly and confirm it returns an image.
- View page source and confirm `og:image` resolves to the correct URL.
- Share URL in a social preview/debugger (platform-dependent) if available.

## Glossary quick refs

- kirby://glossary/template
- kirby://glossary/roots
- kirby://glossary/content-representation

## Links
- Cookbook: Dynamic Open Graph images: https://getkirby.com/docs/cookbook/content-representations/dynamic-og-images
- Guide: Content representations: https://getkirby.com/docs/guide/templates/content-representations
- Quicktip: OpenGraph: https://getkirby.com/docs/quicktips/opengraph
