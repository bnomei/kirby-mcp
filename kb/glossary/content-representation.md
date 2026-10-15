# Content representation (aliases: `.json` templates, “representation template”, `blog.json.php`)

## Meaning

Content representations let you render the **same page** in different formats by adding template variants like `<template>.json.php`, `<template>.txt.php`, etc.

This is commonly used for lightweight project-local JSON endpoints (e.g. “load more” Ajax) or RSS feeds.

## Request a format with Accept

Kirby 6 also selects existing representation templates from the HTTP `Accept` header. For `article.json.php`, request `Accept: application/json` at the page's ordinary URL; for `article.md.php`, use `Accept: text/markdown`. Explicit `.json`/`.md` URLs remain available. The representation must exist and its MIME type must be recognized.

```sh
curl -i -H 'Accept: application/json' https://example.com/blog/article
curl -i -H 'Accept: text/markdown' https://example.com/blog/article
```

Verify body and Content-Type against the explicit-extension request and an HTML control. Configure shared caches/CDNs to distinguish negotiated representations (for example through an appropriate `Vary: Accept` policy); do not assume a cache keyed only by URL is safe. Render-tool `contentType` tests do not exercise HTTP negotiation.

For Kirby 5 clients, retain explicit-extension requests until the CMS is upgraded; adding an Accept header alone does not create this behavior there. Source: https://getkirby.com/releases/6/more-details#content-negotiation.

## In prompts (what it usually implies)

- “Add JSON output” usually means: create a `<template>.json.php` template (and optionally a `<template>.json.php` controller).
- “Render `.json`” means: request the representation or render it via tooling with content type `json`.
- “API endpoint” might mean content representation (simple) or Kirby’s API (more formal).

## Variants / aliases

- Representation template: `…/templates/<template>.<type>.php` (e.g. `blog.json.php`)
- Representation controller: `…/controllers/<template>.<type>.php` (e.g. `blog.json.php`)

## Example

```php
// site/templates/blog.json.php
echo json_encode([
    'title' => $page->title()->value(),
    'items' => $page->children()->listed()->pluck('id'),
]);
```

## MCP: Inspect/verify

- Use `kirby_templates_index` / `kirby_controllers_index` to confirm the representation template/controller exists.
- Render the representation with `kirby_render_page` using `contentType: json` to capture output and errors (install runtime commands via `kirby_runtime_install` if prompted).
- If the representation depends on page content, inspect fields via `kirby_read_page_content` first.

## Related terms

- kirby://glossary/template
- kirby://glossary/controller
- kirby://glossary/page
- kirby://glossary/route

## Links

- https://getkirby.com/docs/guide/templates/content-representations
