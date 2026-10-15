---
name: kirby-plugin-development
description: Builds or extends Kirby plugins using hooks, extensions, blocks, KirbyTags, and shared templates/controllers. Use when creating reusable features or integrating Panel customizations.
---

# Kirby Plugin Development

Target Kirby 6 / MCP 6.x (beta.1 migration baseline); MCP 1.x is the legacy Kirby 5 line. Verify the installed CMS and consult `kirby://kb/panel/compat-k5-k6-migration` before porting Panel code.

## Kirby 6 plugin contracts

- Register PHP field classes, not arrays. Use `DisplayField` for former custom sections, `InputField` for input and `ValueField` for value-only fields. Built-in sections auto-convert; custom sections do not.
- Rebuild Panel code for Vue 3 with kirbyup 4 / Node.js 24+ at beta.1. Import Vue from `vue`, preserve `value`/`input`, declare emits and never bundle a second Vue runtime.
- Bundle compatible kirbyuse if needed. Tagged beta.1 only maps `vue`: do not invent `importMaps` or `panel.extend` APIs.
- Use trusted-HTML APIs only with escaped/sanitized input. Use Panel instance APIs and route controller `action`/`factory()` contracts; preserve valid closures.
- Auth methods/challenges are instance classes under `Kirby\Auth`; pending login is not authenticated. Hash challenge secrets and test recovery/denied paths.

## KB entry points

- `kirby://kb/scenarios/04-share-templates-controllers-via-plugin`
- `kirby://kb/scenarios/05-kirbytext-kirbytags-hooks`
- `kirby://kb/scenarios/15-custom-blocks-nested-blocks`
- `kirby://kb/scenarios/17-extend-kirbytags`
- `kirby://kb/scenarios/59-monolithic-plugin-setup`
- `kirby://kb/scenarios/60-plugin-workflow-local-testing`

## Required inputs

- Plugin id (vendor/name) and scope.
- Extension points (hooks/value or display fields/tags/blocks/areas).
- Distribution plan (project-only or composer package).

## Minimal plugin skeleton

```php
Kirby::plugin('vendor/name', [
  'hooks' => [],
  'blueprints' => [],
  'snippets' => [],
]);
```

## Local testing

- Use the local workflow guide to test without publishing.
- Render affected pages and verify plugin registration.

## Common pitfalls

- Using inconsistent plugin ids or folder names.
- Registering hooks that assume Panel or API is always enabled.

## Workflow

1. Define the plugin id (vendor/name), feature scope, and whether it must be reusable across projects.
2. Call `kirby:kirby_init` and read `kirby://roots` to locate plugin roots.
3. Inspect existing plugins to avoid duplication: `kirby:kirby_plugins_index`.
4. Use extension and hook references:
   - `kirby://extensions` and `kirby://extension/{name}`
   - `kirby://hooks` and `kirby://hook/{name}`
5. Search the KB with `kirby:kirby_search` (examples: "kirbytext hooks", "extend kirbytags", "custom blocks", "share templates via plugin", "monolithic plugin setup").
6. Implement the plugin with a minimal `index.php` registration, then add blueprints/snippets/assets as needed.
7. Verify by rendering affected pages with `kirby:kirby_render_page` and confirming the plugin loads without errors.
