# Scenario: Programmable (PHP-based) blueprints via plugin

## Goal
Generate blueprint definitions dynamically in PHP, e.g.:
- load different blueprints per role (admin vs editor)
- generate field definitions programmatically
- centralize complex blueprint logic in code

## Inputs to ask for
- Which blueprint ids should be programmable (e.g. `site`, `pages/notes`, `fields/...`)
- Condition(s) (user role, environment, feature flags)
- Whether output is YAML loaded from disk or PHP arrays

## Internal tools/resources to use
- Inspect resolved blueprints (incl. plugin overrides):
  - `kirby_blueprints_index`
  - `kirby_blueprint_read` / `kirby://blueprint/{encodedId}`
- Inspect plugins: `kirby_plugins_index`

## Implementation steps
1. Create a plugin and register blueprint ids under the `blueprints` extension.
2. Provide a callback that returns:
   - `Data::read(...yml)` result, or
   - `include ...php` result (PHP array)
3. Keep conditions deterministic and avoid untrusted inputs.

## Examples

### Different site blueprint per role (YAML loaded via `Data::read`)
```php
<?php

use Kirby\Cms\App as Kirby;
use Kirby\Data\Data;

Kirby::plugin('cookbook/programmable-blueprints', [
  'blueprints' => [
    'site' => function () {
      if (($user = kirby()->user()) && $user->isAdmin()) {
        return Data::read(__DIR__ . '/blueprints/site.admin.yml');
      }

      return Data::read(__DIR__ . '/blueprints/site.editor.yml');
    },
  ],
]);
```

### PHP blueprint file (returns array)
```php
use Kirby\Cms\App as Kirby;

Kirby::plugin('cookbook/programmable-blueprints', [
  'blueprints' => [
    'pages/notes' => function ($kirby) {
      return include __DIR__ . '/blueprints/pages/notes.php';
    },
  ],
]);
```

## Verification
- Use `kirby_blueprint_read` for the target blueprint id and confirm it resolves.
- Check Panel behavior for different user roles (admin/editor).

## Glossary quick refs

- kirby://glossary/blueprint
- kirby://glossary/plugin
- kirby://glossary/role
- kirby://glossary/field

## Links
- Cookbook: PHP-based blueprints: https://getkirby.com/docs/cookbook/development-deployment/programmable-blueprints
