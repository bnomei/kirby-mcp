# Collection (aliases: `Collection`, `collection()`, “named collection”)

## Meaning

In Kirby, “collection” can mean two related things:

1. A **collection object** (pages/files/users) that you can filter/sort/paginate.\n
2. A **named collection** (a reusable query) that you define once and reuse across templates/snippets/controllers/plugins via `collection('name')`.

Named collections are a clean way to centralize common queries (e.g. “latest articles”) and keep templates simple.

## In prompts (what it usually implies)

- “Make this query reusable” means: define a named collection and call it from templates/controllers.
- “Where is collection X defined?” means: look in the collections root (often `site/collections`) and/or plugin extensions.
- “Why does `collection('x')` return null?” means: the collection is not registered/loaded, or the helper is disabled (rare).

## Variants / aliases

- Helper: `collection('name')`
- `$kirby->collections()` (list) and `$kirby->collection('name')` (fetch)
- Collections root (often `site/collections`, but root-aware)
- Plugins can register collections via the `collections` extension

## Example

```php
// site/collections/articles.php
return function () {
    return page('blog')->children()->listed()->sortBy('date', 'desc');
};
```

## MCP: Inspect/verify

- Resolve the real collections location with `kirby_roots` (root key: `collections`).
- List available named collections with `kirby_collections_index` (run `kirby_runtime_install` to include plugin-registered ones).
- Confirm a specific collection returns what you expect:
  - example (eval): `return collection('articles')->pluck('id');`
- If collections are plugin-provided, inspect plugins with `kirby_plugins_index` and consult `kirby://extension/collections`.

## Related terms

- kirby://glossary/pages
- kirby://glossary/files
- kirby://glossary/plugin
- kirby://glossary/field-method

## Links

- https://getkirby.com/docs/guide/templates/collections
- https://getkirby.com/docs/reference/templates/helpers/collection
- https://getkirby.com/docs/reference/plugins/extensions/collections
- https://getkirby.com/docs/reference/plugins/extensions/collection-methods
