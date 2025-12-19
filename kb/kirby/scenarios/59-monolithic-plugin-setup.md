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
1. Keep plugin code in `site/plugins/<plugin>/`.
2. Keep the “site for testing” separated (own entrypoint like `index.site.php`).
3. Ensure the shipped archive excludes `content/`, `accounts/`, and other site-specific files.

## Examples
- Use `.gitattributes` `export-ignore` rules to exclude `content/` and other site-only folders from release archives.
- Use a dedicated entrypoint like `index.site.php` for the bundled demo site.

## Verification
- Create a release archive and confirm it contains only the plugin code.
- Install the release into a clean Kirby project and confirm it boots.

## Glossary quick refs

- kirby://glossary/plugin
- kirby://glossary/roots

## Links
- Cookbook: Monolithic plugin setup: https://getkirby.com/docs/cookbook/plugins/monolithic-plugin-setup
- Guide: Plugins: https://getkirby.com/docs/guide/plugins
