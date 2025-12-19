# Page model (aliases: `ProjectPage`, `DefaultPage`, `site/models/*`)

## Meaning

Page models extend Kirby’s default page object (`$page`). They let you define custom methods that are available everywhere you work with pages of that type.

Page models can be placed in the `models` root (often `/site/models`) or registered via a plugin (`pageModels` extension).

## In prompts (what it usually implies)

- “Add a method to `$page`” means: create a page model (or register one via plugin) and call the method on pages of that template type.
- “Default behavior for all pages” can mean: define a `DefaultPage` model (used when no specific model exists).
- “Why doesn’t `$page->cover()` exist?” usually means: the page model isn’t loaded for that page type (naming mismatch).

## Variants / aliases

- `/site/models/<template>.php` with a `<Template>Page` class
- `DefaultPage` (fallback model)
- Plugin registration via `pageModels`

## Example

```php
<?php

class ProjectPage extends Page
{
    public function cover()
    {
        return $this->image('cover.jpg') ?? $this->images()->first();
    }
}
```

## MCP: Inspect/verify

- Use `kirby_models_index` to see which models exist and which ones are active (install runtime commands via `kirby_runtime_install` for runtime truth).
- Use `kirby_plugins_index` to check if a plugin registers page models.
- Use `kirby_eval` to confirm the method exists at runtime:
  - example: `return method_exists(page('projects/project-a'), 'cover');`
- Validate usage via `kirby_render_page` (templates/snippets calling the model method).

## Related terms

- kirby://glossary/page
- kirby://glossary/template
- kirby://glossary/plugin

## Links

- https://getkirby.com/docs/guide/templates/page-models
- https://getkirby.com/docs/reference/plugins/extensions/page-models
