# Scenario: Fine-tune cache exclusions via blueprints/fields

## Goal
Control Kirby page caching dynamically by excluding pages from the cache based on:
- a blueprint option (per page type)
- a field toggle (editor-controlled per page)

## Inputs to ask for
- Whether page cache is enabled and for which pages
- Whether exclusions should be developer-only (blueprint option) or editor-controlled (field)
- Which pages should never be cached (forms, personalization)

## Internal tools/resources to use
- Inspect cache config: `kirby://config/cache`
- Inspect blueprints: `kirby://blueprint/{encodedId}`
- Validate behavior by rendering and checking cache headers (outside MCP)

## Implementation steps
1. Enable page caching in config (if not already).
2. Add either:
   - `options.cache: false` in a blueprint (page-type level), or
   - a `cache` toggle field (per-page)
3. Set `cache.pages.ignore` to a callback that reads the option/field.

## Examples (quicktip patterns)

### Ignore based on a blueprint option
```php
'cache' => [
  'pages' => [
    'active' => true,
    'ignore' => function ($page) {
      $options = $page->blueprint()->options();
      return isset($options['cache']) ? !$options['cache'] : false;
    }
  ]
]
```

### Ignore based on a per-page toggle field
```php
'cache' => [
  'pages' => [
    'active' => true,
    'ignore' => fn ($page) => $page->cache()->toBool(),
  ]
]
```

## Verification
- Confirm excluded pages are not served from cache after multiple requests.

## Glossary quick refs

- kirby://glossary/page
- kirby://glossary/cache
- kirby://glossary/option
- kirby://glossary/blueprint

## Links
- Quicktip: Fine tuning the cache: https://getkirby.com/docs/quicktips/fine-tuning-the-cache
- Guide: Cache: https://getkirby.com/docs/guide/cache
