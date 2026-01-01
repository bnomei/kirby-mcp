# Template (aliases: “page template”, `site/templates/*.php`)

## Meaning

Templates are PHP files that **render pages**. In a standard setup they live in the `templates` root (often `/site/templates`) and have access to `$page`, `$site`, `$kirby`, `$pages`, etc.

Template selection is tied to the page’s template name (usually derived from the content filename and blueprint/template setup). Kirby falls back to `default.php` if no matching template exists.

## In prompts (what it usually implies)

- “Edit the template” means: change the matching PHP file and re-render the page to verify output.
- “Default template” means: `default.php` is used when no specific template exists.
- “Template not found” often means: mismatch between content filename/template name/blueprint setup.

## Variants / aliases

- Template files: `…/templates/<name>.php`
- Representation templates: `…/templates/<name>.<type>.php` (see kirby://glossary/content-representation)
- Can be registered/overridden by plugins (runtime index shows “active” vs “overridden”)

## Example

```php
<?php snippet('header') ?>

<main>
  <h1><?= $page->title()->escape() ?></h1>
  <?= $page->text()->kt() ?>
</main>

<?php snippet('footer') ?>
```

## MCP: Inspect/verify

- Discover the real template root with `kirby_roots`.
- Use `kirby_templates_index` to find the template id and active file path (install runtime commands via `kirby_runtime_install` to include plugin-registered templates).
- Validate output with `kirby_render_page`.
- If you’re unsure about template naming/resolution, use `kirby_online` (“template basics”, “default.php”, etc.).

## Related terms

- kirby://glossary/controller
- kirby://glossary/snippet
- kirby://glossary/page-model
- kirby://glossary/blueprint
- kirby://glossary/content-representation

## Links

- https://getkirby.com/docs/guide/templates/basics
- https://getkirby.com/docs/guide/templates
