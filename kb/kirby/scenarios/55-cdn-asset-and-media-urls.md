# Scenario: Serve assets/media via CDN (components override)

## Goal
Route URLs for assets and/or media files through a CDN without manually rewriting every URL in templates.

## Inputs to ask for
- CDN base URL (zone URL) and which paths should be CDN-backed (`assets/`, `media/`, both)
- Whether only images should be routed through CDN (common) or all files
- Cache-busting strategy (query string, versioned paths, etc.)

## Internal tools/resources to use
- Inspect plugin/extension points: `kirby://extensions` + `kirby://extension/{name}`
- Confirm roots: `kirby://roots`
- Validate rendered HTML output URLs: `kirby_render_page`

## Implementation steps
1. Configure CDN base URL in config (or env).
2. Create a plugin that overrides relevant components:
   - `url` component for static assets
   - `file::url` and/or `file::version` for media
3. Keep an escape hatch: only route specific paths/types.

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

## Verification
- Render a page and confirm `assets/...` URLs point to the CDN base URL.
- Confirm Panel, API, and media URLs still work as expected.

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
