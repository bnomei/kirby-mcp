# Controller (aliases: “template controller”, `site/controllers/*.php`)

## Meaning

Controllers are PHP files that hold **template logic**. They live in the `controllers` root (often `/site/controllers`) and are named like the template they belong to.

A controller returns an anonymous function that returns an array of variables. Those variables are then available in the template.

## In prompts (what it usually implies)

- “Move logic out of the template” means: create/update a controller and return computed data.
- “Access `$page`/`$site`/`$kirby` in controller” means: add those parameters to the controller function; Kirby injects them.
- “Shared controller data” may mean: using a `site.php` controller to merge defaults into all templates.

## Variants / aliases

- Page-specific controller: `…/controllers/<template>.php`
- Site controller: `…/controllers/site.php` (merged into page controllers)
- Representation controllers: `…/controllers/<template>.<type>.php` (see kirby://glossary/content-representation)

## Example

```php
<?php

return function ($page) {
    return [
        'cover' => $page->images()->first(),
    ];
};
```

## MCP: Inspect/verify

- Use `kirby_roots` to locate the effective controllers root.
- Use `kirby_controllers_index` to check whether a controller exists for a given template (runtime install recommended).
- Validate the template receives controller variables by rendering the page via `kirby_render_page`.
- Use `kirby_online` for edge cases like passing route data into controllers.

## Related terms

- kirby://glossary/template
- kirby://glossary/snippet
- kirby://glossary/route

## Links

- https://getkirby.com/docs/guide/templates/controllers
- https://getkirby.com/docs/cookbook/development-deployment/shared-controllers
