# Scenario: Multi-site variant (shared Kirby core + per-site folders)

## Goal

Run multiple Kirby sites that share a single Kirby core installation while keeping separate:

- `site/` code
- `content/` and `media/`
- config and assets

This pattern is useful but often involves server/filesystem setup (symlinks, vhosts).

## Inputs to ask for

- How many sites and how they should be separated (domains/subdomains/paths)
- Whether filesystem symlinks are possible in the target hosting environment
- Whether each site needs a different Kirby version (rare, but possible)

## Internal tools/resources to use

- Confirm current roots: `kirby://roots`
- Confirm runtime boot and version: `kirby://info`

## Implementation steps

1. Follow the cookbook structure (filesystem layout + symlinked `kirby/`).
2. Adjust `index.php` to point Kirby to the correct roots per site.
3. Ensure each site has its own license and configuration.

## Examples (conceptual, see cookbook for full layout)

```php
// index.php chooses correct roots and renders
echo (new Kirby)->render();
```

## Verification

- Confirm each domain/site resolves to its own content and panel.
- Confirm media/cache separation matches your intended architecture.

## Glossary quick refs

- kirby://glossary/roots
- kirby://glossary/layout
- kirby://glossary/media

## Links

- Cookbook: Multisite variant: https://getkirby.com/docs/cookbook/development-deployment/multisite-variant
- Guide: Installation: https://getkirby.com/docs/guide/installation
