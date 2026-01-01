# Roots (aliases: `$kirby->roots()`, `$kirby->root()`, “Kirby roots”)

## Meaning

In Kirby, “roots” are the resolved filesystem paths to important directories like `content`, `site`, `templates`, `snippets`, `blueprints`, `media`, etc.

Roots can be customized at bootstrap time (e.g. to use a `public/` folder or move `content/` outside the webroot). That’s why tooling must never assume default paths.

## In prompts (what it usually implies)

- “Where is `site/…`?” means: check the effective roots; they may be customized.
- “Why is the template/snippet not found?” can be caused by unexpected roots or multiple sources (filesystem vs plugin-registered).
- “Public folder setup” often means: custom roots in `index.php`.

## Variants / aliases

- `$kirby->roots()` (directory structure)
- `$kirby->root('templates')` (single root by key)
- `kirby()->roots()` / `kirby()->root(...)`
- “Root keys” like `content`, `site`, `templates`, `snippets`, `blueprints`, `media`, …

## Example

```php
$roots = kirby()->roots();
$templatesRoot = kirby()->root('templates');
```

## MCP: Inspect/verify

- Use `kirby_roots` (or `kirby://roots`) before any file-oriented work.
- Use the returned root paths to decide where templates/snippets/controllers/models/blueprints actually live.
- If you need the official definition and root keys, use `kirby_online` (“roots Kirby”) or open the reference links below.

## Related terms

- kirby://glossary/kirby
- kirby://glossary/template
- kirby://glossary/snippet
- kirby://glossary/blueprint

## Links

- https://getkirby.com/docs/reference/system/roots
- https://getkirby.com/docs/reference/objects/cms/app/roots
- https://getkirby.com/docs/guide/configuration/custom-folder-setup
