# Scenario: Share templates/controllers across page types via plugin registration

## Goal
Use a single implementation file for multiple templates/controllers, while keeping the option to override per-project in `site/templates` and `site/controllers`.

Typical use case:
- multiple page types (`jobs`, `events`, `news`) share the same grid template/controller logic

## Inputs to ask for
- Which template names should share the same implementation file
- Where the shared code should live (plugin name/id)
- Whether you also want to share page models

## Internal tools/resources to use
- Check plugin root + existing plugins: `kirby_plugins_index`
- Confirm roots/paths: `kirby://roots` (or `kirby_roots`)
- Verify rendering: `kirby_render_page`

## Implementation steps
1. Create a plugin folder:
   - `site/plugins/<your-plugin>/index.php`
2. Register templates/controllers in the plugin:
   - map multiple template names to the same `__DIR__` file
   - controllers are registered by `require`-ing a PHP file that returns a callable
3. Optional: register `pageModels` the same way
4. Remember: project-level templates/controllers override plugin ones automatically:
   - if `site/templates/jobs.php` exists, it wins over the plugin’s `jobs` template mapping

## Examples
`site/plugins/shared-grid/index.php`

```php
<?php

use Kirby\Cms\App as Kirby;

Kirby::plugin('acme/shared-grid', [
    'templates' => [
        'jobs'   => __DIR__ . '/templates/grid.php',
        'events' => __DIR__ . '/templates/grid.php',
        'news'   => __DIR__ . '/templates/grid.php',
    ],
    'controllers' => [
        'jobs'   => require __DIR__ . '/controllers/grid.php',
        'events' => require __DIR__ . '/controllers/grid.php',
        'news'   => require __DIR__ . '/controllers/grid.php',
    ],
]);
```

## Verification
- Render at least one page for each template name and confirm they behave identically.
- Add a one-off override in `site/templates/<name>.php` and confirm it replaces the plugin mapping.

## Glossary quick refs

- kirby://glossary/template
- kirby://glossary/controller
- kirby://glossary/plugin
- kirby://glossary/roots

## Links
- Quicktip: Sharing templates across blueprints: https://getkirby.com/docs/quicktips/different-blueprints-same-template
- Guide: Plugins (overview): https://getkirby.com/docs/guide/plugins
- Reference: Template extension: https://getkirby.com/docs/reference/plugins/extensions/templates
- Reference: Controller extension: https://getkirby.com/docs/reference/plugins/extensions/controllers
