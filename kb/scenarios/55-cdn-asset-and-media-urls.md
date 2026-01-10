# Scenario: Serve assets/media via CDN (components override)

## Goal

Route URLs for assets and/or media files through a CDN without manually rewriting every URL in templates.

## Inputs to ask for

- CDN base URL (zone URL) and which paths should be CDN-backed (`assets/`, `media/`, both)
- Whether only images should be routed through CDN (common) or all files
- Whether CDN should handle image transformations (use `file::version`) or keep Kirby thumbs local
- Cache-busting strategy (query string, versioned paths, etc.)

## Internal tools/resources to use

- Inspect plugin/extension points: `kirby://extensions` + `kirby://extension/{name}`
- Confirm roots: `kirby://roots`
- Validate rendered HTML output URLs: `kirby_render_page`

## Implementation steps

1. Configure CDN base URL in config (or env).
2. Create a plugin that overrides the `url` component for static assets and calls the native component for non-CDN paths.
3. Override `file::url` (affects file + thumb URLs) and optionally `file::version` if the CDN should process image variations.
4. Keep an escape hatch: only route specific paths/types and allow a config toggle.

## Examples

```php
Kirby::plugin('acme/cdn', [
  'components' => [
    'url' => function (Kirby\Cms\Kirby $kirby, string $path, ?string $safeName = null) {
      $native = $kirby->nativeComponent('url');
      $url = $native($kirby, $path, $safeName);

      if (Str::startsWith($path, 'assets')) {
        return rtrim(option('cdn.url'), '/') . '/' . $path;
      }

      return $url;
    },
  ],
]);
```

```php
'file::url' => function (Kirby\Cms\Kirby $kirby, Kirby\Cms\File $file): string {
  $original = $kirby->nativeComponent('file::url');

  if ($file->type() !== 'image') {
    return $original($kirby, $file);
  }

  $path = Kirby\Http\Url::path($file->mediaUrl());
  return rtrim(option('cdn.url'), '/') . '/' . $path;
},
```

## Verification

- Render a page and confirm `assets/...` URLs point to the CDN base URL.
- Confirm `file()->url()` and `thumb()` URLs resolve correctly.
- Confirm Panel, API, and non-CDN URLs still work as expected.

## Glossary quick refs

- kirby://glossary/asset
- kirby://glossary/media
- kirby://glossary/component
- kirby://glossary/plugin

## Links

- Cookbook: Kirby loves CDN: https://getkirby.com/docs/cookbook/performance/kirby-loves-cdn
- Reference: URL component: https://getkirby.com/docs/reference/plugins/components/url
- Reference: `file::url` component: https://getkirby.com/docs/reference/plugins/components/file-urls
- Reference: `file::version` component: https://getkirby.com/docs/reference/plugins/components/file-version
