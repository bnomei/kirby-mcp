# Snippet (aliases: `snippet()`, `site/snippets/*.php`)

## Meaning

Snippets are reusable template partials. They live in the `snippets` root (often `/site/snippets`) and are included from templates/controllers/other snippets.

You include snippets with the `snippet()` helper, optionally pass data, and optionally return the rendered HTML as a string.

## In prompts (what it usually implies)

- “Extract this into a snippet” means: create a snippet file and call it from templates.
- “Pass variables to a snippet” means: pass an associative array as the second argument to `snippet()`.
- “Snippet fallback” can mean: using “snippet alternatives” (array of snippet names).

## Variants / aliases

- Snippet file: `…/snippets/<name>.php` (folders allowed: `…/snippets/articles/card.php`)
- `snippet('header')`
- `snippet('header', ['class' => 'blog'])`
- `snippet('header', [], true)` (return rendered output)
- `snippet(['a', 'b'])` (alternatives)
- Can be registered/overridden by plugins (runtime index shows “active” vs “overridden”)

## Example

```php
<?php snippet('header', ['class' => 'blog']) ?>

<?= snippet(['articles/' . $page->template()->name(), 'articles/default'], ['page' => $page], true) ?>
```

## MCP: Inspect/verify

- Use `kirby_roots` to locate the effective snippets root.
- Use `kirby_snippets_index` to list snippet ids and active file paths (install runtime commands via `kirby_runtime_install` to include plugin-registered snippets).
- Validate snippet output by rendering the parent page with `kirby_render_page`.
- For helper signature and options (return/slots), consult official docs via `kirby_online` or the reference page.

## Related terms

- kirby://glossary/template
- kirby://glossary/controller
- kirby://glossary/page

## Links

- https://getkirby.com/docs/guide/templates/snippets
- https://getkirby.com/docs/reference/templates/helpers/snippet
- https://getkirby.com/docs/reference/plugins/extensions/snippets
