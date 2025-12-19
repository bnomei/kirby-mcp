# Scenario: Filter listings via routes (pretty tag/category URLs)

## Goal

Use Kirby routes so URLs like these work:

- `/blog/tag/travel`
- `/blog/travel` (optional, but watch for conflicts with real subpages)

Instead of parameter URLs like `/blog/tag:travel`.

## Inputs to ask for

- Which base page renders the listing (e.g. `blog`)
- Which filter variable you want (tag/category/year/…)
- Whether filter values can contain spaces/special chars (then `urlencode`/`urldecode`)
- Whether filtered pages should reuse the blog template or get their own template

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (or `kirby_roots`)
- List registered routes (runtime truth): `kirby_routes_index` (requires `kirby_runtime_install`)
- Render and inspect the base page/template: `kirby_render_page` (note: it doesn’t execute the router itself)
- Route basics: `kirby_search` for “routing” (if you need refresher quickly)

## Implementation steps

1. Add a route that captures the filter value and renders the base page with extra data.
2. In the base page controller, accept the injected `$tag` (or other variable) and filter.
3. If you want `/blog/<value>` URLs:
   - first check if `page('blog/' . $value)` exists and return it to avoid conflicts

## Examples

### Route: `/blog/tag/<value>`

`site/config/config.php`

```php
<?php

return [
  'routes' => [
    [
      'pattern' => 'blog/tag/(:any)',
      'action'  => function ($tag) {
        return page('blog')->render(['tag' => $tag]);
      }
    ]
  ]
];
```

### Controller (blog): accept the injected `$tag`

`site/controllers/blog.php`

```php
<?php

return function ($page, $tag = null) {
  $articles = $page->children()->listed();

  if ($tag) {
    $articles = $articles->filterBy('tags', $tag, ',');
  }

  return [
    'articles' => $articles,
  ];
};
```

### Optional: `/blog/<value>` route with conflict check

```php
return [
  'routes' => [
    [
      'pattern' => 'blog/(:any)',
      'action'  => function ($tag) {
        if ($page = page('blog/' . $tag)) return $page;
        return page('blog')->render(['tag' => $tag]);
      }
    ]
  ]
];
```

## Verification

- Visit the route URLs and confirm:
  - filtering works
  - real blog subpages still resolve when present
- Confirm the route is registered and locate its definition with `kirby_routes_index(patternContains='blog/tag')`.

## Glossary quick refs

- kirby://glossary/route
- kirby://glossary/controller
- kirby://glossary/roots
- kirby://glossary/template

## Links

- Cookbook: Filtering via routes: https://getkirby.com/docs/cookbook/collections/filter-via-route
- Guide: Routes: https://getkirby.com/docs/guide/routing
