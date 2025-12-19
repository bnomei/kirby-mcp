# Scenario: Improve IDE support (type hints + generated helpers)

## Goal

Make templates/controllers/models easier to work with by improving type inference, and optionally generate helper files for IDEs.

## Inputs to ask for

- Which editor/IDE is used (PHPStorm, VS Code, etc.)
- Which parts need better inference (templates, controllers, page models)
- Whether generating helper files is acceptable (regeneratable artifacts)

## Internal tools/resources to use

- IDE helper tooling:
  - `kirby_ide_helpers_status`
  - `kirby_generate_ide_helpers`
- Inventory code locations: `kirby_templates_index`, `kirby_controllers_index`, `kirby_models_index`

## Implementation steps

1. Add PHPDoc in templates for variables inside loops when needed.
2. Type-hint controller closure arguments.
3. Ensure page models extend the correct Kirby classes.
4. Optionally generate project-local IDE helpers via MCP.

## Examples

### Controller type hints

```php
<?php

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Cms\Pages;
use Kirby\Cms\Site;

return function (Site $site, Page $page, Pages $pages, App $kirby) {
  // ...
};
```

### Page model class

```php
<?php

use Kirby\Cms\Page;

class NotePage extends Page
{
  // ...
}
```

## Verification

- `kirby_ide_helpers_status` to check what’s missing/outdated.
- Re-run `kirby_generate_ide_helpers` and confirm IDE picks up improved types.

## Glossary quick refs

- kirby://glossary/controller
- kirby://glossary/template
- kirby://glossary/status

## Links

- Quicktip: Improve IDE support: https://getkirby.com/docs/quicktips/ide-support
