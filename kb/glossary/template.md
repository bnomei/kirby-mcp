# Template (aliases: “page template”, `site/templates/*.php`)

## Meaning

Templates are PHP files that **render pages**. In a standard setup they live in the `templates` root (often `/site/templates`) and have access to `$page`, `$site`, `$kirby`, `$pages`, etc.

Template selection is tied to the page’s template name (usually derived from the content filename and blueprint/template setup). Kirby falls back to `default.php` if no matching template exists.

Use template stacks to collect output from snippets and render it elsewhere. Reserve `push()`, `endpush()` and `stack()` for Kirby, and `$slot`/`$slots` for template slots. For a Kirby 5 migration, rename application helpers or controller data that collide with these names.

## Collect block assets with stacks

In the header snippet, place `<?php stack('styles') ?>` inside the head. In each block snippet, push its stylesheet:

```php
<?php push('styles', css('assets/css/gallery.css'), unique: true) ?>
```

The stack placeholder is resolved after the template finishes, so the header can precede the block. `unique: true` deduplicates identical output, not filenames with different markup. For buffered markup use `push('scripts')`, output the markup, then `endpush()`.

`stack()` echoes by default; `return: true` returns its placeholder. Stacks clear after rendering by default: to render the same stack twice, use `clear: false` on the first call. See https://getkirby.com/releases/6/template-stacks.

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
