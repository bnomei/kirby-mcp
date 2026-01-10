# Scenario: Replace/extend Kirby core classes (App/Site)

## Goal

Replace Kirby core classes when you need deeper control than hooks/extensions allow, e.g.:

- custom `App` boot logic
- custom `Site` methods or behavior

## Inputs to ask for

- Which class needs replacing (`Kirby\Cms\App`, `Kirby\Cms\Site`, …)
- Why hooks/plugins aren’t sufficient (be explicit)
- Whether modifying `index.php` is acceptable for this project

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (or `kirby_roots`)
- Inventory plugins: `kirby_plugins_index`
- Validate runtime: `kirby_render_page` (after changes)

## Implementation steps

1. Create a plugin that contains your custom class(es) and registers them in `index.php`.
2. Update `index.php` to instantiate your custom `App` class (if replacing `App`).
3. If replacing `Site`, override `site()`/`setSite()` in your custom `App` to return your `CustomSite`.
4. Keep replacements minimal; prefer composition and small overrides.

## Examples (from the cookbook recipe; simplified)

### Custom App class file

`site/plugins/extend-core-classes/classes/CustomKirby.php`

```php
<?php

namespace cookbook\core;

use Kirby\Cms\App;

class CustomKirby extends App
{
}
```

### Use custom App in `index.php`

```php
<?php

require __DIR__ . '/kirby/bootstrap.php';
require __DIR__ . '/site/plugins/extend-core-classes/classes/CustomKirby.php';

use cookbook\core\CustomKirby;

echo (new CustomKirby)->render();
```

## Verification

- Load the site and confirm it boots correctly.
- Add a temporary `mcp_dump()` in the custom class, render with `kirby_render_page`, then inspect via `kirby_dump_log_tail(traceId=...)` and remove the dump.

## Glossary quick refs

- kirby://glossary/plugin
- kirby://glossary/roots
- kirby://glossary/hook

## Links

- Cookbook: Replacing core classes: https://getkirby.com/docs/cookbook/development-deployment/replacing-core-classes
- Guide: Plugins: https://getkirby.com/docs/guide/plugins
