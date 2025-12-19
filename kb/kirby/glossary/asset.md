# Asset (aliases: `asset()`, `Kirby\Filesystem\Asset`, `assets/…`)

## Meaning

In Kirby, an “asset” usually means a file that’s part of your project’s static assets (CSS/JS/images in an `assets/` folder) and is **not** a Kirby content file object. You can still create an object wrapper for it with the `asset()` helper to get its URL and metadata.

Important: `asset()` can access arbitrary filesystem paths, so you should never pass user-controlled paths to it.

## In prompts (what it usually implies)

- “Reference `/assets/...`” means: use static URLs or `asset('assets/...')->url()` if you need an object.
- “Difference between asset and file” means:
  - _asset_: arbitrary filesystem file (not managed in the Panel as content)
  - _file_: Kirby content file attached to a page/site/user

## Variants / aliases

- `asset('assets/images/logo.svg')`
- Asset object methods: `$asset->url()`, `$asset->exists()`, `$asset->read()` (depending on use)
- Related helpers: `css()`, `js()` (generate asset URLs for CSS/JS)

## Example

```php
<?php if ($asset = asset('assets/images/logo.svg')): ?>
  <img src="<?= $asset->url() ?>" alt="">
<?php endif ?>
```

## MCP: Inspect/verify

- Use `kirby_roots` to confirm where the project expects “assets” to live (don’t assume a folder name).
- Use `kirby_render_page` to verify asset URLs in real HTML output.
- Avoid “dynamic asset paths” sourced from `$request`; if that’s the prompt, treat it as a security review.

## Related terms

- kirby://glossary/file
- kirby://glossary/thumb
- kirby://glossary/roots
- kirby://glossary/option

## Links

- https://getkirby.com/docs/reference/templates/helpers/asset
- https://getkirby.com/docs/reference/objects/filesystem/asset
