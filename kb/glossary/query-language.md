# Query language (aliases: “blueprint query”, `query:` in blueprints)

## Meaning

Kirby's blueprint query language queries pages/files/users and formats values with dot notation. Kirby 6 always uses the new runner for field options, list fields and query templates.

## In prompts (what it usually implies)

- “Set options from pages” means: use a query to fetch pages and map them to select options.
- “Show extra info in a pages section” means inspect the existing section and its normalized `pagelist` field; use query templates for the displayed text.
- “Query is slow” means inspect the collection scope and work done by custom methods, not switch runners.

## Variants / aliases

- Used inside `options` for option fields and in candidate `query` settings for pickers
- Can use page models and custom methods (same as PHP API surface)
- Kirby 5 legacy `query.runner` configuration must be removed for Kirby 6.

## Example

```yaml
fields:
  related:
    type: select
    options:
      type: query
      query: site.index
      text: '{{ page.title }}'
      value: '{{ page.id }}'
```

## Runner and option-source behavior

Queries support logical/comparison/math operators, closure arguments and subscript access. A dash is subtraction. Inspect the actual content key: TXT decoding normalizes `My-field:` to `my_field`, so use `page.content.get("my_field")`, not `page.my-field`. Quoting a key avoids subtraction but does not reverse storage-key normalization. Unknown field methods throw.

- Negation and unary minus work: `!page.isListed`, `-page.price`, `-(1 + 2)`.
- Comparisons return booleans: `page.price.toFloat > 5 && page.stock.toInt > 0`.
- `??`, `?:`, ternaries, `&&` and `||` evaluate only the operands they need. Do not rely on side effects in an unused branch.
- String backslashes are preserved except for escaped quotes and backslashes. Check both YAML quoting and query-string escaping when passing regex patterns.
- `{{ 0 }}` resolves to `0`; an empty placeholder `{{ }}` remains unchanged.

For custom query integrations, use `Kirby\Query\Query::resolve()` or a `Runners\Runner`, not the removed Argument/Expression/Segment classes. A custom `Visitors\Visitor` must implement its abstract methods and accept closures for lazy right operands/branches.

Kirby 5 legacy `options: query` plus a separate `query.fetch` block becomes the nested `options.type`/`options.query` example above. API sources likewise move under `options`. Associative option queries use keys as values by default; explicit `text`/`value` mappings remain useful for model collections. Invalid option values are preserved in Kirby 6 rather than silently deleted on save.

For API-backed field options, interpolate only trusted values into the source URL. User-controlled URLs can cause server-side request forgery (SSRF), including access to internal services. Keep Kirby patched for fixes to dynamic option rendering; URL trust is still the application's responsibility.

## MCP: Inspect/verify

- Inspect the exact blueprint config via `kirby_blueprint_read` (or `kirby://blueprint/{encodedId}`) to see the query that is actually applied (incl. `extends`).
- Use `kirby_online` for query syntax and examples (“blueprint query language”, “options query fetch text value”).
- Use `kirby_query_dot` to evaluate dot-notation query strings against runtime context; pass `model` to set `$page/$file/$user` (page id or UUID like `page://...`, `file://...`, `user://...`, user email, file path with extension, or `site`).
- Example (page context): evaluate `page.siblings.count` with `model=notes`.
- `kirby_query_dot` context includes `kirby`, `site`, `model`, and `page`/`file`/`user` aliases plus `currentUser` when logged in.
- For debugging the underlying PHP API logic, replicate the query in `kirby_eval` with real objects (site/page/pages).

## Related terms

- kirby://glossary/blueprint
- kirby://glossary/extends
- kirby://glossary/pages
- kirby://glossary/site

## Links

- https://getkirby.com/docs/guide/blueprints/query-language
- https://getkirby.com/releases/6/migration/site-developers#queries
- https://getkirby.com/docs/reference/panel/fields/select#options
- https://github.com/getkirby/kirby/releases/tag/5.3.0
