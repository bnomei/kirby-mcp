# Scenario: Monolithic plugin setup (plugin + bundled dev site)

## Goal

Develop a Kirby plugin inside a repository that also contains a full Kirby site for local testing/demos, then ship only the plugin code.

## Inputs to ask for

- Whether the plugin needs a Panel build step (Vue components)
- Whether the repo should include a dedicated test site (content, templates) or only fixtures
- How the plugin will be distributed (zip download, composer, git submodule)

## Internal tools/resources to use

- Confirm roots: `kirby://roots` (in a Kirby project)
- Inventory plugins: `kirby_plugins_index`
- Confirm “how to run” tooling: `kirby://composer`

## Implementation steps

1. Keep plugin code at repo root (`index.php`, `package.json`, `src/`, `dist/`).
2. Add a Kirby test site in the same repo and use `index.site.php` as the site entrypoint.
3. Add a proxy plugin in `site/plugins/<plugin>/index.php` that requires the root plugin `index.php`.
4. Ensure the shipped archive excludes `content/`, `kirby/`, `site/`, `media/`, `accounts/`, and other site-specific files.

## Examples

- Use `.gitattributes` `export-ignore` rules to exclude `content/` and other site-only folders from release archives.
- Use a dedicated entrypoint like `index.site.php` for the bundled demo site.
- Update `.htaccess` to route requests to `index.site.php`.
- Proxy plugin loader:

```php
<?php
require dirname(__DIR__, 3) . '/index.php';
```

## Verification

- Create a release archive and confirm it contains only the plugin code.
- Install the release into a clean Kirby project and confirm it boots.

## Glossary quick refs

- kirby://glossary/plugin
- kirby://glossary/roots

## Links

- Cookbook: Monolithic plugin setup: https://getkirby.com/docs/cookbook/plugins/monolithic-plugin-setup
- Guide: Plugins: https://getkirby.com/docs/guide/plugins
