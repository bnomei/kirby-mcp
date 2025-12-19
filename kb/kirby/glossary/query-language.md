# Query language (aliases: “blueprint query”, `query:` in blueprints)

## Meaning

Kirby’s blueprint query language lets you **query pages/files/users** and format values using a dot-notation syntax that mirrors Kirby’s PHP API. It is used heavily in Panel blueprints (field options, pages/files sections, informational templates, etc.).

## In prompts (what it usually implies)

- “Set options from pages” means: use a query to fetch pages and map them to select options.
- “Show extra info in a pages section” means: use query string templates for list items.
- “Query is slow” may relate to the (optional) `query.runner` system option (Kirby 5.1+).

## Variants / aliases

- Used in blueprint field option sources (`options: query`) and the `query` option in some field/section types
- Can use page models and custom methods (same as PHP API surface)
- Related system option: `query.runner`

## Example

```yaml
fields:
  related:
    type: select
    options: query
    query:
      fetch: site.index
      text: "{{ page.title }}"
      value: "{{ page.id }}"
```

## MCP: Inspect/verify

- Inspect the exact blueprint config via `kirby_blueprint_read` (or `kirby://blueprint/{encodedId}`) to see the query that is actually applied (incl. `extends`).
- Use `kirby_online` for query syntax and examples (“blueprint query language”, “options query fetch text value”).
- For debugging the underlying PHP API logic, replicate the query in `kirby_eval` with real objects (site/page/pages).

## Related terms

- kirby://glossary/blueprint
- kirby://glossary/extends
- kirby://glossary/pages
- kirby://glossary/site

## Links

- https://getkirby.com/docs/guide/blueprints/query-language
- https://getkirby.com/docs/reference/system/options/query
