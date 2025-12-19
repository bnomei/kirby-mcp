# Site (aliases: `$site`, `Kirby\Cms\Site`, `site()`)

## Meaning

The “site” is the **root model** of a Kirby installation. It represents global site content (“site fields”), the page tree entry point, available languages, users, etc.

In templates/snippets/controllers, Kirby provides the site object as `$site`. Outside of that context (or in extensions), you can retrieve it from `$kirby` or via the `site()` helper.

## In prompts (what it usually implies)

- “Site title/description” means: a site field like `$site->title()` or `$site->description()`.
- “All pages” often starts from the site: `$site->index()` (full tree) or `$site->children()` (top level).
- “Find page by id” often means: `$site->find('blog')` or `page('blog')`.

## Variants / aliases

- `$site` (available in templates/snippets/controllers)
- `site()` helper
- `$kirby->site()`
- Class: `Kirby\Cms\Site`
- Common related calls: `$site->index()`, `$site->find()`, `$site->children()`, `$site->files()`

## Example

```php
<header>
  <h1><?= $site->title()->escape() ?></h1>
  <p><?= $site->description()->escape() ?></p>
</header>
```

## MCP: Inspect/verify

- Call `kirby_roots` first to understand the real `content` and `site` locations.
- Use `kirby_eval` for small checks like `return site()->title()->value();` or `return site()->content()->toArray();`.
- When debugging template output that uses `$site`, render a concrete page with `kirby_render_page` (the template will have `$site` available).
- Look up site methods and examples via `kirby_online` (query: “$site <method>”).

## Related terms

- kirby://glossary/kirby
- kirby://glossary/page
- kirby://glossary/pages
- kirby://glossary/user
- kirby://glossary/users
- kirby://glossary/language
- kirby://glossary/languages
- kirby://glossary/field
- kirby://glossary/roots

## Links

- https://getkirby.com/docs/reference/objects/cms/site
- https://getkirby.com/docs/reference/templates/helpers/site
