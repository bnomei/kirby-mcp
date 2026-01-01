# Scenario: Add `sitemap.xml` (snippet + route)

## Goal

Provide a `sitemap.xml` endpoint for search engines without maintaining a static file.

## Inputs to ask for

- Which pages to include/exclude (drafts, error page, private sections)
- Multi-language behavior (one sitemap vs per-language)
- Update frequency / caching needs

## Internal tools/resources to use

- Inspect routes config: `kirby://config/routes`
- List registered routes (runtime truth): `kirby_routes_index(patternContains='sitemap')` (requires `kirby_runtime_install`)
- Validate output by rendering route response (manual or via browser)
- Inventory snippets: `kirby_snippets_index`

## Implementation steps

1. Create a sitemap snippet that outputs XML.
2. Add a route for `sitemap.xml` that:
   - builds a pages index
   - renders the snippet as a string
   - returns an XML response
3. Add an optional redirect route from `/sitemap` → `/sitemap.xml`.
4. Add a config option like `sitemap.ignore` for exclusions.

## Examples (cookbook pattern; abridged)

### Route definition

```php
return [
  'routes' => [
    [
      'pattern' => 'sitemap.xml',
      'action'  => function () {
        $pages = site()->pages()->index();
        $ignore = option('sitemap.ignore', ['error']);
        $content = snippet('sitemap', compact('pages', 'ignore'), true);
        return new Kirby\Cms\Response($content, 'application/xml');
      }
    ],
    [
      'pattern' => 'sitemap',
      'action'  => fn () => go('sitemap.xml', 301),
    ],
  ],
];
```

## Verification

- Open `/sitemap.xml` and validate the XML.
- Confirm excluded pages don’t appear.
- Confirm the route is registered and locate its definition with `kirby_routes_index(patternContains='sitemap')`.

## Glossary quick refs

- kirby://glossary/route
- kirby://glossary/snippet
- kirby://glossary/language
- kirby://glossary/option

## Links

- Cookbook: Sitemap: https://getkirby.com/docs/cookbook/navigation/sitemap
- Guide: Routing: https://getkirby.com/docs/guide/routing
