# Thumb / thumbnails (aliases: `$file->thumb()`, `$image->resize()`, `thumbs` option)

## Meaning

Kirby generates image thumbnails (“thumbs”) on the fly when you call image manipulation methods like `thumb()`, `resize()`, `crop()`, or `srcset()`. Thumbs are generated into the `media` folder and can be configured globally via the `thumbs` config options (quality, presets, srcsets, driver, format, etc.).

## In prompts (what it usually implies)

- “Resize/crop image” means: use `$image->resize(...)` / `$image->crop(...)` and output `$image->url()`.
- “Use WebP/AVIF thumbs” means: set `thumbs.format` (or pass `format` per call).
- “Srcset” means: use `$image->srcset(...)` and configure `thumbs.srcsets` if needed.
- “Different thumbs locally vs production” often means: differing `content.salt` or path-dependent hashing.

## Variants / aliases

- `$image->thumb([...])` (full options)
- Shortcut methods: `$image->resize()`, `$image->crop()`, `$image->grayscale()`, `$image->blur()`
- Config: `thumbs.presets`, `thumbs.srcsets`, `thumbs.driver`, `thumbs.quality`, `thumbs.format`
- Core component override: plugins can replace the `thumb` component

## Example

```php
<img
  src="<?= $image->resize(600)->url() ?>"
  srcset="<?= $image->srcset([600, 1200]) ?>"
  alt=""
>
```

## MCP: Inspect/verify

- Confirm thumb configuration via `kirby://config/thumbs` (requires `kirby_runtime_install`).
- Confirm `media` root via `kirby_roots` and validate generated URLs by rendering with `kirby_render_page`.
- If a prompt mentions “custom thumb generator”, inspect installed plugins + the `core-components` extension:
  - `kirby://extension/core-components` and kirby://glossary/component

## Related terms

- kirby://glossary/file
- kirby://glossary/media
- kirby://glossary/option
- kirby://glossary/component

## Links

- https://getkirby.com/docs/guide/files/resize-images-on-the-fly
- https://getkirby.com/docs/reference/system/options/thumbs
- https://getkirby.com/docs/reference/objects/cms/file/thumb
- https://getkirby.com/docs/reference/plugins/components/thumb
