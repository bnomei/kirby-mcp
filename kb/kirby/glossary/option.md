# Option / config option (aliases: `option()`, `$kirby->option()`, `kirby://config/{option}`)

## Meaning

Kirby “options” are configuration values that control core behavior (e.g. `debug`, `cache`, `routes`, `languages`, `thumbs`, `session`) and plugin behavior. They are usually defined in `site/config/config.php` (and optionally in environment-specific config files) and read at runtime with `$kirby->option()` or the `option()` helper.

Options are a common source of “it works locally but not on production” issues because Kirby can load different config files based on host/CLI and because some options must be set _before_ Kirby fully boots.

## In prompts (what it usually implies)

- “Set the `debug`/`cache`/`url` option” means: edit `site/config/config.php` (or a host-specific config file) and verify the effective runtime value.
- “Where is the config?” means: don’t assume `site/`; confirm with `kirby_roots` first.
- “I need dynamic config” often means: use the `ready` option callback (because `$kirby` isn’t available while config loads).

## Variants / aliases

- `option('key', $default)` (helper)
- `$kirby->option('key', $default)` (method)
- “Dot notation” for nested options: `option('cache.pages.active')`
- Config file(s):
  - `site/config/config.php`
  - `site/config/config.<host>.php` (multi-environment)
  - `site/config/cli.php` (CLI overrides)
  - `site/config/env.php` (deployment overrides)
- `ready` option: run code after Kirby is initialized (safe place for `$kirby` access)

## Example

```php
<?php

if (option('debug') === true) {
    echo '<!-- debug enabled -->';
}
```

## MCP: Inspect/verify

- Prefer the config resource first (runtime truth):
  - read `kirby://config/debug` (or any other option path) and re-check after changes
  - if you see the “needs runtime install” message, run `kirby_runtime_install` once and retry
- Use `kirby_roots` to locate the effective `config` root before editing files.
- For the `routes` option specifically, compare `kirby://config/routes` (config-defined routes) with `kirby_routes_index` (all registered routes, including plugins).
- If you need to confirm in PHP, use `kirby_eval` (read-only) like `return option('cache');`.

## Related terms

- kirby://glossary/kirby
- kirby://glossary/roots
- kirby://glossary/cache
- kirby://glossary/route
- kirby://glossary/language
- kirby://glossary/session

## Links

- https://getkirby.com/docs/guide/configuration
- https://getkirby.com/docs/reference/templates/helpers/option
- https://getkirby.com/docs/reference/system/options
- https://getkirby.com/docs/reference/system/options/ready
